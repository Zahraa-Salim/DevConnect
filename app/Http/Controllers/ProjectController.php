<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectIdea;
use App\Models\ProjectRole;
use App\Models\ProjectMember;
use App\Models\ProjectPulseLog;
use App\Models\InviteLink;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\TeamAgreement;
use App\Models\Conversation;
use App\Models\Rating;
use App\Jobs\UpdateContributionDnaJob;
use App\Services\AiChemistryService;
use App\Services\AiProjectMatchService;
use App\Services\ProjectReadinessService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $aiMatchEnabled = $request->boolean('ai_match') && auth()->check();

        $query = Project::query()
            ->where('status', '!=', 'archived')
            ->with([
                'owner:id,name,avatar_url',
                'members' => fn($q) => $q->where('status', 'active'),
                'roles' => fn($q) => $q->where('is_open', true),
            ]);

        // Filter by status
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Filter by type
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        // Filter by domain
        if ($domain = $request->query('domain')) {
            $query->where('domain', 'like', "%{$domain}%");
        }

        // Search by title
        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->boolean('mine')) {
            $query->where(function ($q) {
                $q->whereHas('members', fn ($sq) =>
                    $sq->where('user_id', auth()->id())->where('status', 'active')
                )->orWhere('owner_id', auth()->id());
            });
        }

        if ($aiMatchEnabled) {
            $projects = $this->applyAiRanking($query->latest()->get(), $request);
        } else {
            $projects = $query->latest()->paginate(12)->withQueryString();
        }

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'filters' => [
                ...$request->only(['status', 'type', 'domain', 'search']),
                'mine' => $request->boolean('mine'),
            ],
            'aiMatchEnabled' => $aiMatchEnabled,
        ]);
    }

    private function applyAiRanking(Collection $projects, Request $request): LengthAwarePaginator
    {
        $user = auth()->user();
        $cacheKey = "ai_match_{$user->id}";

        $rankings = Cache::remember($cacheKey, 3600, function () use ($user, $projects) {
            try {
                return (new AiProjectMatchService())->rankProjects($user, $projects);
            } catch (\RuntimeException $e) {
                Log::warning('AI Project Match unavailable', ['error' => $e->getMessage()]);
                return [];
            }
        });

        if (!empty($rankings)) {
            $scoreMap = collect($rankings)->keyBy('id');

            $projects = $projects
                ->map(function ($project) use ($scoreMap) {
                    $match = $scoreMap->get($project->id);
                    $project->setAttribute('ai_score', $match['score'] ?? 0);
                    $project->setAttribute('ai_reason', $match['reason'] ?? '');
                    return $project;
                })
                ->sortByDesc('ai_score')
                ->values();
        }

        $perPage = 12;
        $page = (int) $request->query('page', 1);

        return new LengthAwarePaginator(
            $projects->forPage($page, $perPage)->values(),
            $projects->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }

    public function create(Request $request): Response
    {
        $idea = null;

        if ($ideaId = $request->query('idea_id')) {
            $idea = ProjectIdea::where('id', $ideaId)
                ->whereIn('status', ['active', 'featured'])
                ->first();

            // Don't pre-fill from already-converted ideas
            if ($idea && $idea->status === ProjectIdea::STATUS_CONVERTED) {
                $idea = null;
            }
        }

        return Inertia::render('Projects/Create', [
            'idea' => $idea,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'type' => ['required', 'in:practice,real_client'],
            'domain' => ['nullable', 'string', 'max:80'],
            'tech_stack' => ['nullable', 'array'],
            'tech_stack.*' => ['string', 'max:80'],
            'max_members' => ['nullable', 'integer', 'min:1', 'max:20'],
            'estimated_duration' => ['nullable', 'string', 'max:50'],
            'idea_id' => ['nullable', 'exists:project_ideas,id'],
            'roles' => ['nullable', 'array'],
            'roles.*.role_name' => ['required', 'string', 'max:80'],
            'roles.*.slots' => ['required', 'integer', 'min:1', 'max:20'],
            'roles.*.description' => ['nullable', 'string', 'max:500'],
        ]);

        // Check for double conversion
        if ($ideaId = $validated['idea_id'] ?? null) {
            $idea = ProjectIdea::findOrFail($ideaId);

            if ($idea->status === ProjectIdea::STATUS_CONVERTED) {
                return back()->withErrors([
                    'idea_id' => 'This idea has already been converted to a project.',
                ]);
            }
        }

        // Create the project
        $project = Project::create([
            'owner_id' => auth()->id(),
            'idea_id' => $validated['idea_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'domain' => $validated['domain'] ?? null,
            'tech_stack' => $validated['tech_stack'] ?? null,
            'max_members' => $validated['max_members'] ?? 5,
            'estimated_duration' => $validated['estimated_duration'] ?? null,
            'status' => Project::STATUS_OPEN,
        ]);

        // Auto-add creator as first member
        $project->members()->create([
            'user_id' => auth()->id(),
            'role' => auth()->user()->role ?? 'owner',
            'status' => 'active',
            'access_level' => 3,
            'joined_at' => now(),
        ]);

        // Auto-create project group chat
        $groupConversation = Conversation::create([
            'type' => Conversation::TYPE_GROUP,
            'thread_type' => Conversation::THREAD_MAIN,
            'project_id' => $project->id,
            'last_message_at' => now(),
        ]);

        // Add owner as first participant
        $groupConversation->participants()->attach(auth()->id());

        $defaultTasks = [
            ['title' => 'Set up project repository and folder structure', 'energy' => 'quick_win', 'priority' => 'high', 'status' => 'todo', 'position' => 0],
            ['title' => 'Write project README and contribution guide', 'energy' => 'quick_win', 'priority' => 'medium', 'status' => 'todo', 'position' => 1],
            ['title' => 'Define team roles and responsibilities', 'energy' => 'deep_work', 'priority' => 'high', 'status' => 'todo', 'position' => 2],
        ];

        foreach ($defaultTasks as $task) {
            Task::create([
                'project_id' => $project->id,
                'assigned_to' => null,
                'title' => $task['title'],
                'energy' => $task['energy'],
                'priority' => $task['priority'],
                'status' => $task['status'],
                'position' => $task['position'],
            ]);
        }

        // Create project roles
        if ($roles = $validated['roles'] ?? null) {
            foreach ($roles as $role) {
                $project->roles()->create([
                    'role_name' => $role['role_name'],
                    'slots' => $role['slots'],
                    'description' => $role['description'] ?? null,
                    'filled' => 0,
                    'is_open' => true,
                ]);
            }
        }

        // If created from an idea, mark the idea as converted
        if ($ideaId = $validated['idea_id'] ?? null) {
            ProjectIdea::where('id', $ideaId)->update([
                'status' => ProjectIdea::STATUS_CONVERTED,
                'converted_project_id' => $project->id,
            ]);
        }

        return redirect()
            ->route('projects.show', $project->id)
            ->with('success', "Project \"{$project->title}\" created successfully!");
    }

    public function show(Project $project): Response
    {
        $isMember = auth()->check()
            ? $project->members()
                ->where('user_id', auth()->id())
                ->where('status', 'active')
                ->exists()
            : false;

        $hasApplied = auth()->check()
            ? $project->applications()
                ->where('user_id', auth()->id())
                ->where('status', 'pending')
                ->exists()
            : false;

        $project->load([
            'owner:id,name,avatar_url,role',
            'roles' => fn($q) => $q->orderBy('is_open', 'desc'),
            'members' => fn($q) => $q->where('status', 'active')->with('user:id,name,avatar_url,role,reputation_score,is_verified'),
            'idea',
        ]);

        $isOwner = auth()->id() === $project->owner_id;

        $pendingApplications = $isOwner
            ? $project->applications()
                ->where('status', 'pending')
                ->with(['user:id,name,avatar_url,role', 'user.workingStyle', 'user.skills', 'role:id,role_name'])
                ->latest()
                ->get()
                ->map(function ($app) use ($project) {
                    try {
                        $chemistry = app(AiChemistryService::class)->analyze($project, $app->user);
                    } catch (\Throwable) {
                        $chemistry = ['label' => 'Unknown', 'alignment' => [], 'friction' => [], 'summary' => 'Analysis unavailable'];
                    }
                    $app->chemistry = $chemistry;
                    return $app;
                })
            : collect();

        $userApplication = auth()->check()
            ? $project->applications()
                ->where('user_id', auth()->id())
                ->where('status', 'pending')
                ->with('role:id,role_name')
                ->first()
            : null;

        $inviteLinks = $isOwner
            ? InviteLink::where('project_id', $project->id)
                ->with('role:id,role_name')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function (InviteLink $link): InviteLink {
                    $link->setAttribute('full_url', rtrim(config('app.url'), '/') . '/invite/' . $link->token);
                    $link->setAttribute('is_valid', (! $link->expires_at || $link->expires_at->isFuture())
                        && (! $link->max_uses || $link->uses < $link->max_uses));

                    return $link;
                })
            : [];

        $memberRecord = auth()->check()
            ? ProjectMember::where('project_id', $project->id)
                ->where('user_id', auth()->id())
                ->where('status', 'active')
                ->first()
            : null;

        $userNdaAgreement = auth()->check() && $project->type === Project::TYPE_REAL_CLIENT
            ? TeamAgreement::where('project_id', $project->id)
                ->where('user_id', auth()->id())
                ->where('type', TeamAgreement::TYPE_NDA)
                ->first()
            : null;

        $userTeamAgreement = auth()->check() && $project->type === Project::TYPE_REAL_CLIENT
            ? TeamAgreement::where('project_id', $project->id)
                ->where('user_id', auth()->id())
                ->where('type', TeamAgreement::TYPE_TEAM_AGREEMENT)
                ->first()
            : null;

        $milestones = $project->type === Project::TYPE_REAL_CLIENT
            ? $project->milestones()->orderBy('order_index')->get()
            : [];

        $agreementText = $project->type === Project::TYPE_REAL_CLIENT
            ? TeamAgreement::where('project_id', $project->id)
                ->where('user_id', $project->owner_id)
                ->where('type', TeamAgreement::TYPE_TEAM_AGREEMENT)
                ->latest('signed_at')
                ->value('document_text')
            : null;

        $ndaRequired = $project->type === Project::TYPE_REAL_CLIENT
            && $isMember
            && ! $isOwner
            && ! $userNdaAgreement;

        // Sprint data
        $activeSprint = null;
        $sprints = [];
        $taskCounts = ['todo' => 0, 'in_progress' => 0, 'done' => 0, 'backlog' => 0];

        if ($isMember || $isOwner) {
            $activeSprint = Sprint::where('project_id', $project->id)
                ->where('status', 'active')
                ->first();

            if ($activeSprint) {
                $activeSprint->days_remaining;
                $activeSprint->total_points;
                $activeSprint->completed_points;
                $activeSprint = array_merge($activeSprint->toArray(), [
                    'days_remaining' => $activeSprint->days_remaining,
                    'total_points' => $activeSprint->total_points,
                    'completed_points' => $activeSprint->completed_points,
                ]);
            }

            $sprints = Sprint::where('project_id', $project->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function (Sprint $sprint): array {
                    return array_merge($sprint->toArray(), [
                        'days_remaining' => $sprint->days_remaining,
                        'total_points' => $sprint->total_points,
                        'completed_points' => $sprint->completed_points,
                    ]);
                });

            $taskCounts = [
                'backlog' => Task::where('project_id', $project->id)->whereNull('sprint_id')->count(),
                'todo' => Task::where('project_id', $project->id)->where('status', 'todo')->whereNotNull('sprint_id')->count(),
                'in_progress' => Task::where('project_id', $project->id)->where('status', 'in_progress')->count(),
                'done' => Task::where('project_id', $project->id)->where('status', 'done')->count(),
            ];
        }

        // Tab badge counts
        $decisionCount = 0;
        $fileCount = 0;
        if ($isMember || $isOwner) {
            $decisionCount = \App\Models\DecisionLog::where('project_id', $project->id)->count();
            $fileCount = \App\Models\ProjectFile::where('project_id', $project->id)->count();
        }

        // Pulse data
        $pulseStatus = null;
        $pulseHistory = [];
        if ($isMember || $isOwner) {
            $latestPulse = ProjectPulseLog::where('project_id', $project->id)
                ->latest('triggered_at')
                ->first();
            $pulseStatus = $latestPulse?->status;

            if ($isOwner) {
                $pulseHistory = ProjectPulseLog::where('project_id', $project->id)
                    ->orderBy('triggered_at', 'desc')
                    ->limit(10)
                    ->get();
            }
        }

        // Get or create group chat for this project
        $groupChatId = null;
        if ($isMember || $isOwner) {
            $groupChat = Conversation::where('project_id', $project->id)
                ->where('type', Conversation::TYPE_GROUP)
                ->where('thread_type', Conversation::THREAD_MAIN)
                ->first();

            if (!$groupChat) {
                // Auto-create group chat for old projects (created before Phase 4)
                $groupChat = Conversation::create([
                    'type' => Conversation::TYPE_GROUP,
                    'thread_type' => Conversation::THREAD_MAIN,
                    'project_id' => $project->id,
                    'last_message_at' => now(),
                ]);

                // Add all active members as participants
                $memberIds = $project->members()->where('status', 'active')->pluck('user_id')->toArray();
                $groupChat->participants()->attach($memberIds);
            }

            $groupChatId = $groupChat->id;
        }

        $hasRated = ($isMember || $isOwner) && auth()->check()
            ? Rating::where('rater_id', auth()->id())
                ->where('project_id', $project->id)
                ->exists()
            : false;

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'isMember' => $isMember,
            'isOwner' => $isOwner,
            'hasApplied' => $hasApplied,
            'hasRated' => $hasRated,
            'pendingApplications' => $pendingApplications,
            'userApplication' => $userApplication,
            'inviteLinks' => $inviteLinks,
            'milestones' => $milestones,
            'ndaRequired' => $ndaRequired,
            'userNdaSigned' => (bool) $userNdaAgreement,
            'userNdaSignedAt' => $userNdaAgreement?->signed_at,
            'userAgreementSigned' => (bool) $userTeamAgreement,
            'userAgreementSignedAt' => $userTeamAgreement?->signed_at,
            'userAccessLevel' => $memberRecord?->access_level ?? 0,
            'agreementText' => $agreementText,
            'groupChatId' => $groupChatId,
            'activeSprint' => $activeSprint,
            'sprints' => $sprints,
            'taskCounts' => $taskCounts,
            'decisionCount' => $decisionCount,
            'fileCount' => $fileCount,
            'pulseStatus' => $pulseStatus,
            'pulseHistory' => $pulseHistory,
        ]);
    }

    public function edit(Project $project): Response
    {
        abort_if(auth()->id() !== $project->owner_id, 403);

        $project->load('roles');

        return Inertia::render('Projects/Edit', [
            'project' => $project,
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'type' => ['required', 'in:practice,real_client'],
            'domain' => ['nullable', 'string', 'max:80'],
            'tech_stack' => ['nullable', 'array'],
            'tech_stack.*' => ['string', 'max:80'],
            'max_members' => ['nullable', 'integer', 'min:1', 'max:20'],
            'estimated_duration' => ['nullable', 'string', 'max:50'],
            'roles' => ['nullable', 'array'],
            'roles.*.id' => ['nullable', 'exists:project_roles,id'],
            'roles.*.role_name' => ['required', 'string', 'max:80'],
            'roles.*.slots' => ['required', 'integer', 'min:1', 'max:20'],
            'roles.*.description' => ['nullable', 'string', 'max:500'],
        ]);

        // Update the project
        $project->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'domain' => $validated['domain'] ?? null,
            'tech_stack' => $validated['tech_stack'] ?? null,
            'max_members' => $validated['max_members'] ?? 5,
            'estimated_duration' => $validated['estimated_duration'] ?? null,
        ]);

        // Sync roles
        $rolesToSync = [];
        if ($roles = $validated['roles'] ?? null) {
            foreach ($roles as $role) {
                if ($roleId = $role['id'] ?? null) {
                    // Update existing role
                    ProjectRole::where('id', $roleId)
                        ->where('project_id', $project->id)
                        ->update([
                            'role_name' => $role['role_name'],
                            'slots' => $role['slots'],
                            'description' => $role['description'] ?? null,
                        ]);
                    $rolesToSync[] = $roleId;
                } else {
                    // Create new role
                    $newRole = $project->roles()->create([
                        'role_name' => $role['role_name'],
                        'slots' => $role['slots'],
                        'description' => $role['description'] ?? null,
                        'filled' => 0,
                        'is_open' => true,
                    ]);
                    $rolesToSync[] = $newRole->id;
                }
            }
        }

        // Delete roles not in the sync list
        $project->roles()->whereNotIn('id', $rolesToSync)->delete();

        return redirect()
            ->route('projects.show', $project->id)
            ->with('success', 'Project updated successfully!');
    }

    public function destroy(Project $project): RedirectResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);

        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project deleted successfully!');
    }

    public function updateStatus(Request $request, Project $project): RedirectResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:open,active,completed,at_risk,archived'],
        ]);

        $newStatus = $validated['status'];
        $currentStatus = $project->status;

        // Enforce valid transitions
        $validTransitions = [
            'open'      => ['active', 'archived'],
            'active'    => ['completed', 'at_risk', 'archived'],
            'at_risk'   => ['active', 'archived'],
            'completed' => ['archived'],
            'archived'  => ['open'],
        ];

        if (!in_array($newStatus, $validTransitions[$currentStatus] ?? [])) {
            return back()->withErrors([
                'status' => "Cannot transition from {$currentStatus} to {$newStatus}.",
            ]);
        }

        // Gate re-publish behind readiness check
        if ($newStatus === 'open') {
            $result = (new ProjectReadinessService())->check($project);
            if (! $result['ready']) {
                $failingMsg = collect($result['checks'])
                    ->filter(fn ($c) => ! $c['pass'])
                    ->pluck('label')
                    ->implode(', ');
                return back()->withErrors([
                    'readiness' => "Project is not ready to publish. Incomplete: {$failingMsg}.",
                ]);
            }
        }

        // Update the status
        $data = ['status' => $newStatus];
        if ($newStatus === 'completed') {
            $data['completed_at'] = now();
        }

        $project->update($data);

        // Refresh DNA for all active members when project completes
        if ($newStatus === 'completed') {
            $project->members()
                ->where('status', 'active')
                ->with('user')
                ->get()
                ->each(fn ($m) => $m->user && UpdateContributionDnaJob::dispatch($m->user));
        }

        return back()->with('success', 'Project status updated successfully!');
    }

    public function readiness(Project $project): \Illuminate\Http\JsonResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);

        return response()->json((new ProjectReadinessService())->check($project));
    }

    public function leave(Project $project): RedirectResponse
    {
        abort_if(auth()->id() === $project->owner_id, 403);

        $member = $project->members()
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->firstOrFail();

        // Update member status
        $member->update([
            'status' => 'left',
            'left_at' => now(),
        ]);

        // Decrement role filled count and reopen if needed
        if ($member->role && $member->role !== 'undecided') {
            $role = $project->roles()
                ->where('role_name', $member->role)
                ->first();

            if ($role) {
                $role->decrement('filled');

                if ($role->filled < $role->slots && !$role->is_open) {
                    $role->update(['is_open' => true]);
                }
            }
        }

        // Remove from project group chat
        $groupChat = Conversation::where('project_id', $project->id)
            ->where('type', Conversation::TYPE_GROUP)
            ->where('thread_type', Conversation::THREAD_MAIN)
            ->first();

        if ($groupChat) {
            $groupChat->participants()->detach(auth()->id());
        }

        return redirect()
            ->route('projects.index')
            ->with('success', 'You have left the project.');
    }

    public function removeMember(Project $project, ProjectMember $member): RedirectResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);
        abort_if($member->project_id !== $project->id, 404);
        abort_if($member->user_id === $project->owner_id, 400);

        // Update member status
        $member->update([
            'status' => 'removed',
            'left_at' => now(),
        ]);

        // Decrement role filled count and reopen if needed
        if ($member->role && $member->role !== 'undecided') {
            $role = $project->roles()
                ->where('role_name', $member->role)
                ->first();

            if ($role) {
                $role->decrement('filled');

                if ($role->filled < $role->slots && !$role->is_open) {
                    $role->update(['is_open' => true]);
                }
            }
        }

        // Remove from project group chat
        $groupChat = Conversation::where('project_id', $project->id)
            ->where('type', Conversation::TYPE_GROUP)
            ->where('thread_type', Conversation::THREAD_MAIN)
            ->first();

        if ($groupChat) {
            $groupChat->participants()->detach($member->user_id);
        }

        return back()->with('success', 'Member removed from project.');
    }
}
