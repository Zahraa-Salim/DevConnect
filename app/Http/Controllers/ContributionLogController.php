<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ContributionLog;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContributionLogController extends Controller
{
    private const STATUS_ORDER = [
        ContributionLog::STATUS_BOOKMARKED   => 0,
        ContributionLog::STATUS_WORKING      => 1,
        ContributionLog::STATUS_PR_SUBMITTED => 2,
        ContributionLog::STATUS_MERGED       => 3,
    ];

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'github_issue_id' => ['required', 'integer', 'exists:github_issues,id'],
        ]);

        $log = ContributionLog::firstOrCreate(
            [
                'user_id'         => auth()->id(),
                'github_issue_id' => $validated['github_issue_id'],
            ],
            ['status' => ContributionLog::STATUS_BOOKMARKED],
        );

        return response()->json($log, $log->wasRecentlyCreated ? 201 : 200);
    }

    public function updateStatus(Request $request, ContributionLog $log): JsonResponse
    {
        abort_if($log->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:working,pr_submitted,merged'],
            'pr_url' => ['nullable', 'string', 'max:500'],
        ]);

        $newStatus   = $validated['status'];
        $currentRank = self::STATUS_ORDER[$log->status] ?? 0;
        $newRank     = self::STATUS_ORDER[$newStatus] ?? 0;

        if ($newRank <= $currentRank) {
            return response()->json(['error' => 'Status can only move forward.'], 422);
        }

        if ($newStatus === ContributionLog::STATUS_PR_SUBMITTED) {
            $prUrl = trim($validated['pr_url'] ?? '');
            if (! preg_match('/github\.com\/.+\/pull\/\d+/', $prUrl)) {
                return response()->json(['error' => 'Must be a valid GitHub PR URL (github.com/.../pull/123).'], 422);
            }
            $log->pr_url = $prUrl;
        }

        $log->status = $newStatus;
        $log->save();

        return response()->json($log);
    }

    public function convertToProject(ContributionLog $log): RedirectResponse
    {
        abort_if($log->user_id !== auth()->id(), 403);
        abort_if($log->converted_project_id !== null, 409);

        $issue = $log->issue;

        $project = Project::create([
            'owner_id'    => auth()->id(),
            'title'       => $issue->title,
            'description' => mb_substr($issue->body ?? '', 0, 500),
            'repo_url'    => "https://github.com/{$issue->repo_full_name}",
            'type'        => Project::TYPE_PRACTICE,
            'status'      => Project::STATUS_OPEN,
        ]);

        $project->members()->create([
            'user_id'      => auth()->id(),
            'role'         => auth()->user()->role ?? 'owner',
            'status'       => 'active',
            'access_level' => 3,
            'joined_at'    => now(),
        ]);

        $log->update(['converted_project_id' => $project->id]);

        return redirect()->route('projects.edit', $project->id);
    }
}
