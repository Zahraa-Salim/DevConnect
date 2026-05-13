<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        $activeProjectCount = ProjectMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->whereHas('project', fn ($q) => $q->whereIn('status', ['open', 'active', 'at_risk']))
            ->count();

        $completedProjectCount = ProjectMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->whereHas('project', fn ($q) => $q->where('status', 'completed'))
            ->count();

        $dna = is_array($user->contribution_dna) ? $user->contribution_dna : [];

        $myProjects = Project::where(function ($q) use ($user) {
            $q->whereHas('members', fn ($sq) =>
                $sq->where('user_id', $user->id)->where('status', 'active')
            )->orWhere('owner_id', $user->id);
        })
            ->whereIn('status', ['open', 'active', 'at_risk'])
            ->with(['members:id', 'roles:id,role_name,is_open'])
            ->orderByDesc('updated_at')
            ->limit(3)
            ->get()
            ->map(fn (Project $p) => [
                'id'           => $p->id,
                'title'        => $p->title,
                'status'       => $p->status,
                'type'         => $p->type,
                'domain'       => $p->domain,
                'tech_stack'   => $p->tech_stack ?? [],
                'member_count' => $p->members->count(),
                'max_members'  => $p->max_members,
                'open_roles'   => $p->roles->where('is_open', true)->pluck('role_name')->values(),
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'reputation_score'        => round((float) ($user->reputation_score ?? 0), 1),
                'is_verified'             => (bool) $user->is_verified,
                'active_project_count'    => $activeProjectCount,
                'completed_project_count' => $completedProjectCount,
                'dna_label'               => $dna['label'] ?? null,
                'tasks_done'              => $dna['tasks_done'] ?? 0,
                'prs_merged'              => $dna['prs_merged'] ?? 0,
            ],
            'myProjects' => $myProjects,
        ]);
    }
}
