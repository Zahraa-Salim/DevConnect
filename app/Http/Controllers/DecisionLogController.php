<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DecisionLog;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DecisionLogController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        $isMember = $project->members()
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->exists();

        abort_unless($isMember || auth()->id() === $project->owner_id, 403);

        $decisions = DecisionLog::where('project_id', $project->id)
            ->with('user:id,name,avatar_url')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($decisions);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $isMember = $project->members()
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->exists();

        abort_unless($isMember || auth()->id() === $project->owner_id, 403);

        $validated = $request->validate([
            'decision' => ['required', 'string', 'max:500'],
            'reason'   => ['nullable', 'string', 'max:2000'],
        ]);

        $entry = DecisionLog::create([
            'project_id' => $project->id,
            'user_id'    => auth()->id(),
            'decision'   => $validated['decision'],
            'reason'     => $validated['reason'] ?? null,
        ]);

        $entry->load('user:id,name,avatar_url');

        return response()->json($entry, 201);
    }

    public function destroy(Project $project, int $decisionId): JsonResponse
    {
        $decision = DecisionLog::where('id', $decisionId)
            ->where('project_id', $project->id)
            ->firstOrFail();

        abort_unless(
            auth()->id() === $decision->user_id || auth()->id() === $project->owner_id,
            403
        );

        $decision->delete();

        return response()->json(['success' => true]);
    }
}
