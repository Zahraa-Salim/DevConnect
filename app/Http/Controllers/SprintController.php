<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SprintController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        $isMember = $project->members()->where('user_id', auth()->id())->where('status', 'active')->exists();
        $isOwner = auth()->id() === $project->owner_id;

        abort_unless($isMember || $isOwner, 403);

        $sprints = Sprint::where('project_id', $project->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Sprint $sprint): array {
                return [
                    'id' => $sprint->id,
                    'project_id' => $sprint->project_id,
                    'name' => $sprint->name,
                    'goal' => $sprint->goal,
                    'start_date' => $sprint->start_date?->toDateString(),
                    'end_date' => $sprint->end_date?->toDateString(),
                    'status' => $sprint->status,
                    'velocity' => $sprint->velocity,
                    'retro_good' => $sprint->retro_good,
                    'retro_improve' => $sprint->retro_improve,
                    'retro_actions' => $sprint->retro_actions,
                    'days_remaining' => $sprint->days_remaining,
                    'total_points' => $sprint->total_points,
                    'completed_points' => $sprint->completed_points,
                    'task_counts' => [
                        'todo' => $sprint->tasks()->where('status', 'todo')->count(),
                        'in_progress' => $sprint->tasks()->where('status', 'in_progress')->count(),
                        'done' => $sprint->tasks()->where('status', 'done')->count(),
                    ],
                    'created_at' => $sprint->created_at,
                ];
            });

        return response()->json(['sprints' => $sprints]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        abort_unless(auth()->id() === $project->owner_id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'goal' => ['nullable', 'string', 'max:500'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        $sprint = Sprint::create([
            ...$validated,
            'project_id' => $project->id,
            'status' => Sprint::STATUS_PLANNING,
        ]);

        return response()->json(['sprint' => $this->formatSprint($sprint)], 201);
    }

    public function update(Request $request, Project $project, Sprint $sprint): JsonResponse
    {
        abort_unless(auth()->id() === $project->owner_id, 403);
        abort_unless($sprint->project_id === $project->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'goal' => ['nullable', 'string', 'max:500'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        $sprint->update($validated);

        return response()->json(['sprint' => $this->formatSprint($sprint->fresh())]);
    }

    public function start(Project $project, Sprint $sprint): JsonResponse
    {
        abort_unless(auth()->id() === $project->owner_id, 403);
        abort_unless($sprint->project_id === $project->id, 404);

        $hasActive = Sprint::where('project_id', $project->id)
            ->where('status', Sprint::STATUS_ACTIVE)
            ->exists();

        abort_if($hasActive, 400, 'Another sprint is already active for this project.');
        abort_if($sprint->tasks()->count() === 0, 400, 'Sprint has no tasks. Add tasks before starting.');

        $sprint->update(['status' => Sprint::STATUS_ACTIVE]);

        return response()->json(['sprint' => $this->formatSprint($sprint->fresh())]);
    }

    public function complete(Request $request, Project $project, Sprint $sprint): JsonResponse
    {
        abort_unless(auth()->id() === $project->owner_id, 403);
        abort_unless($sprint->project_id === $project->id, 404);
        abort_unless($sprint->status === Sprint::STATUS_ACTIVE, 400, 'Only active sprints can be completed.');

        $validated = $request->validate([
            'retro_good' => ['nullable', 'string'],
            'retro_improve' => ['nullable', 'string'],
            'retro_actions' => ['nullable', 'string'],
        ]);

        // Calculate velocity: sum of story_points for done tasks
        $velocity = (int) $sprint->tasks()->where('status', 'done')->sum('story_points');

        // Move incomplete tasks back to product backlog
        $sprint->tasks()->whereIn('status', ['todo', 'in_progress'])->update(['sprint_id' => null]);

        $sprint->update([
            'status' => Sprint::STATUS_COMPLETED,
            'velocity' => $velocity,
            'retro_good' => $validated['retro_good'] ?? null,
            'retro_improve' => $validated['retro_improve'] ?? null,
            'retro_actions' => $validated['retro_actions'] ?? null,
        ]);

        return response()->json(['sprint' => $this->formatSprint($sprint->fresh())]);
    }

    public function destroy(Project $project, Sprint $sprint): JsonResponse
    {
        abort_unless(auth()->id() === $project->owner_id, 403);
        abort_unless($sprint->project_id === $project->id, 404);
        abort_if($sprint->status === Sprint::STATUS_ACTIVE, 400, 'Cannot delete an active sprint.');

        // Move all tasks back to product backlog
        $sprint->tasks()->update(['sprint_id' => null]);
        $sprint->delete();

        return response()->json(['message' => 'Sprint deleted.']);
    }

    private function formatSprint(Sprint $sprint): array
    {
        return [
            'id' => $sprint->id,
            'project_id' => $sprint->project_id,
            'name' => $sprint->name,
            'goal' => $sprint->goal,
            'start_date' => $sprint->start_date?->toDateString(),
            'end_date' => $sprint->end_date?->toDateString(),
            'status' => $sprint->status,
            'velocity' => $sprint->velocity,
            'retro_good' => $sprint->retro_good,
            'retro_improve' => $sprint->retro_improve,
            'retro_actions' => $sprint->retro_actions,
            'days_remaining' => $sprint->days_remaining,
            'total_points' => $sprint->total_points,
            'completed_points' => $sprint->completed_points,
            'task_counts' => [
                'todo' => $sprint->tasks()->where('status', 'todo')->count(),
                'in_progress' => $sprint->tasks()->where('status', 'in_progress')->count(),
                'done' => $sprint->tasks()->where('status', 'done')->count(),
            ],
            'created_at' => $sprint->created_at,
        ];
    }
}
