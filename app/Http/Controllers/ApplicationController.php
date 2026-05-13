<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectRole;
use App\Models\TeamAgreement;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['nullable', 'exists:project_roles,id'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        // Guard: User is not already a member
        if ($project->members()->where('user_id', auth()->id())->where('status', 'active')->exists()) {
            return back()->withErrors([
                'message' => 'You are already a member of this project.',
            ]);
        }

        // Guard: User does not have a pending application
        if ($project->applications()
            ->where('user_id', auth()->id())
            ->where('status', Application::STATUS_PENDING)
            ->exists()) {
            return back()->withErrors([
                'message' => 'You already have a pending application for this project.',
            ]);
        }

        // Guard: Project is open or active
        if (!in_array($project->status, ['open', 'active'])) {
            return back()->withErrors([
                'message' => 'This project is no longer accepting applications.',
            ]);
        }

        // Guard: If role_id provided, validate it
        if ($roleId = $validated['role_id'] ?? null) {
            $role = ProjectRole::where('id', $roleId)
                ->where('project_id', $project->id)
                ->first();

            if (!$role) {
                return back()->withErrors([
                    'message' => 'The selected role does not exist.',
                ]);
            }

            if (!$role->is_open) {
                return back()->withErrors([
                    'message' => 'This role is no longer accepting applications.',
                ]);
            }

            if ($role->filled >= $role->slots) {
                return back()->withErrors([
                    'message' => 'This role is no longer accepting applications.',
                ]);
            }
        }

        // Create application
        Application::create([
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'role_id' => $validated['role_id'] ?? null,
            'message' => $validated['message'] ?? null,
            'status' => Application::STATUS_PENDING,
        ]);

        return back()->with('success', 'Your application has been submitted!');
    }

    public function accept(Request $request, Project $project, Application $application): RedirectResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);
        abort_if($application->project_id !== $project->id, 404);
        abort_if($application->status !== Application::STATUS_PENDING, 400);

        $ndaRequiredForAcceptedMember = false;

        DB::transaction(function () use ($project, $application, &$ndaRequiredForAcceptedMember) {
            // Update application
            $application->update([
                'status' => Application::STATUS_ACCEPTED,
                'decided_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            // Determine role name
            $roleName = 'undecided';
            if ($application->role_id && $application->role) {
                $roleName = $application->role->role_name;
            }

            $hasSignedNda = $project->type !== Project::TYPE_REAL_CLIENT
                || TeamAgreement::where('project_id', $project->id)
                    ->where('user_id', $application->user_id)
                    ->where('type', TeamAgreement::TYPE_NDA)
                    ->exists();

            $ndaRequiredForAcceptedMember = ! $hasSignedNda;

            // Create project member
            $project->members()->create([
                'user_id' => $application->user_id,
                'role' => $roleName,
                'status' => 'active',
                'access_level' => $hasSignedNda ? 1 : 0,
                'joined_at' => now(),
            ]);

            // Increment role filled count and close if full
            if ($application->role_id) {
                $role = $application->role;
                $role->increment('filled');

                if ($role->filled >= $role->slots) {
                    $role->update(['is_open' => false]);
                }
            }

            // Auto-create DM between owner and new member
            $existingDm = \App\Models\Conversation::where('type', \App\Models\Conversation::TYPE_DM)
                ->whereHas('participants', fn ($q) => $q->where('user_id', auth()->id()))
                ->whereHas('participants', fn ($q) => $q->where('user_id', $application->user_id))
                ->first();

            if (!$existingDm) {
                $dm = \App\Models\Conversation::create([
                    'type' => \App\Models\Conversation::TYPE_DM,
                    'thread_type' => \App\Models\Conversation::THREAD_MAIN,
                    'last_message_at' => now(),
                ]);
                $dm->participants()->attach([auth()->id(), $application->user_id]);

                $welcomeMessage = \App\Models\Message::create([
                    'conversation_id' => $dm->id,
                    'sender_id' => auth()->id(),
                    'body' => "Welcome to {$project->title}! Feel free to ask me anything about the project.",
                ]);

                $welcomeMessage->load('sender:id,name,avatar_url');
                broadcast(new \App\Events\NewMessage($welcomeMessage))->toOthers();
            }

            // Add new member to project group chat
            $groupChat = \App\Models\Conversation::where('project_id', $project->id)
                ->where('type', \App\Models\Conversation::TYPE_GROUP)
                ->where('thread_type', \App\Models\Conversation::THREAD_MAIN)
                ->first();

            if ($groupChat) {
                $groupChat->participants()->syncWithoutDetaching([$application->user_id]);

                // Send system message announcing the new member
                $joinMessage = \App\Models\Message::create([
                    'conversation_id' => $groupChat->id,
                    'sender_id' => $application->user_id,
                    'body' => auth()->user()->name . " accepted " . $application->user->name . "'s application. Welcome to the team!",
                ]);
                $groupChat->update(['last_message_at' => now()]);
                broadcast(new \App\Events\NewMessage($joinMessage->load('sender')))->toOthers();
            }
        });

        $applicantName = $application->user->name;
        if ($ndaRequiredForAcceptedMember) {
            return back()->with(
                'success',
                "Application accepted. {$applicantName} has joined the project. Member added but NDA signing required. They will need to sign the NDA to access the workspace."
            );
        }

        return back()->with('success', "Application accepted. {$applicantName} has joined the project.");
    }

    public function decline(Request $request, Project $project, Application $application): RedirectResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);
        abort_if($application->project_id !== $project->id, 404);
        abort_if($application->status !== Application::STATUS_PENDING, 400);

        $application->update([
            'status' => Application::STATUS_DECLINED,
            'decided_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Application declined.');
    }

    public function withdraw(Project $project, Application $application): RedirectResponse
    {
        abort_if(auth()->id() !== $application->user_id, 403);
        abort_if($application->project_id !== $project->id, 404);
        abort_if($application->status !== Application::STATUS_PENDING, 400);

        $application->update([
            'status' => Application::STATUS_WITHDRAWN,
        ]);

        return back()->with('success', 'Your application has been withdrawn.');
    }
}
