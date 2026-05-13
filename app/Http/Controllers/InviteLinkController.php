<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InviteLink;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectRole;
use App\Models\TeamAgreement;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InviteLinkController extends Controller
{
    public function store(Request $request, Project $project): JsonResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);

        $validated = $request->validate([
            'role_id' => ['nullable', 'exists:project_roles,id'],
            'expires_in' => ['nullable', 'in:1h,6h,24h,7d,30d,never'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $role = null;
        if ($roleId = $validated['role_id'] ?? null) {
            $role = ProjectRole::findOrFail($roleId);

            abort_if($role->project_id !== $project->id, 422, 'The selected role does not belong to this project.');
            abort_if(! $role->is_open || $role->filled >= $role->slots, 422, 'The selected role is no longer open.');
        }

        $invite = InviteLink::create([
            'project_id' => $project->id,
            'role_id' => $role?->id,
            'token' => $this->uniqueToken(),
            'expires_at' => $this->expiresAt($validated['expires_in'] ?? '7d'),
            'max_uses' => $validated['max_uses'] ?? null,
            'uses' => 0,
        ]);

        $invite->load('role:id,role_name');
        $invite->setAttribute('full_url', $this->inviteUrl($invite));
        $invite->setAttribute('is_valid', $this->isValid($invite));

        return response()->json([
            'link' => $this->inviteUrl($invite),
            'invite' => $invite,
        ]);
    }

    public function show(string $token): Response
    {
        $invite = InviteLink::where('token', $token)
            ->with([
                'project.owner:id,name,avatar_url',
                'role',
            ])
            ->firstOrFail();

        return Inertia::render('Invite/Show', [
            'invite' => $invite,
            'isExpired' => $invite->expires_at && $invite->expires_at->isPast(),
            'isMaxed' => $invite->max_uses && $invite->uses >= $invite->max_uses,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invite = InviteLink::where('token', $token)
            ->with(['project', 'role'])
            ->firstOrFail();

        if ($invite->expires_at && $invite->expires_at->isPast()) {
            return redirect()->route('invite.show', $token)->with('error', 'This invite link has expired.');
        }

        if ($invite->max_uses && $invite->uses >= $invite->max_uses) {
            return redirect()->route('invite.show', $token)->with('error', 'This invite link has reached its usage limit.');
        }

        $alreadyMember = ProjectMember::where('project_id', $invite->project_id)
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->exists();

        if ($alreadyMember) {
            return redirect()
                ->route('projects.show', $invite->project_id)
                ->with('info', 'You are already a member of this project');
        }

        if ($invite->project->type === Project::TYPE_REAL_CLIENT) {
            $hasSignedNda = TeamAgreement::where('project_id', $invite->project_id)
                ->where('user_id', auth()->id())
                ->where('type', TeamAgreement::TYPE_NDA)
                ->exists();

            if (! $hasSignedNda) {
                session(['pending_invite_token' => $token]);

                return redirect()->route('projects.nda', $invite->project_id);
            }
        }

        DB::transaction(function () use ($invite): void {
            ProjectMember::create([
                'project_id' => $invite->project_id,
                'user_id' => auth()->id(),
                'role' => $invite->role?->role_name ?? 'undecided',
                'status' => 'active',
                'access_level' => 1,
                'joined_at' => now(),
            ]);

            if ($invite->role_id) {
                $role = ProjectRole::lockForUpdate()->findOrFail($invite->role_id);
                $role->increment('filled');
                $role->refresh();

                if ($role->filled >= $role->slots) {
                    $role->update(['is_open' => false]);
                }
            }

            // Add to project group chat
            $groupChat = Conversation::where('project_id', $invite->project_id)
                ->where('type', Conversation::TYPE_GROUP)
                ->where('thread_type', Conversation::THREAD_MAIN)
                ->first();

            if ($groupChat) {
                $groupChat->participants()->syncWithoutDetaching([auth()->id()]);
            }

            $invite->increment('uses');
        });

        return redirect()
            ->route('projects.show', $invite->project_id)
            ->with('success', 'Welcome to the project!');
    }

    public function index(Project $project): JsonResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);

        $links = InviteLink::where('project_id', $project->id)
            ->with('role:id,role_name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (InviteLink $link): InviteLink {
                $link->setAttribute('full_url', $this->inviteUrl($link));
                $link->setAttribute('is_valid', $this->isValid($link));

                return $link;
            });

        return response()->json($links);
    }

    public function destroy(Project $project, InviteLink $inviteLink): RedirectResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);
        abort_if($inviteLink->project_id !== $project->id, 404);

        $inviteLink->delete();

        return back()->with('success', 'Invite link revoked');
    }

    private function uniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (InviteLink::where('token', $token)->exists());

        return $token;
    }

    private function expiresAt(?string $expiresIn): mixed
    {
        return match ($expiresIn) {
            '1h' => now()->addHour(),
            '6h' => now()->addHours(6),
            '24h' => now()->addDay(),
            '7d' => now()->addDays(7),
            '30d' => now()->addDays(30),
            'never', null => null,
            default => now()->addDays(7),
        };
    }

    private function inviteUrl(InviteLink $invite): string
    {
        return rtrim(config('app.url'), '/') . '/invite/' . $invite->token;
    }

    private function isValid(InviteLink $invite): bool
    {
        return (! $invite->expires_at || $invite->expires_at->isFuture())
            && (! $invite->max_uses || $invite->uses < $invite->max_uses);
    }
}
