<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);
        abort_if($project->type !== Project::TYPE_REAL_CLIENT, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unlocks_access_level' => ['required', 'integer', 'in:1,2,3'],
        ]);

        $orderIndex = ((int) $project->milestones()->max('order_index')) + 1;

        if (! $project->milestones()->exists()) {
            $orderIndex = 0;
        }

        $project->milestones()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'unlocks_access_level' => $validated['unlocks_access_level'],
            'order_index' => $orderIndex,
        ]);

        return back()->with('success', 'Milestone created');
    }

    public function complete(Request $request, Project $project, ProjectMilestone $milestone): RedirectResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);
        abort_if($milestone->project_id !== $project->id, 404);
        abort_if($milestone->completed_at !== null, 400);

        $milestone->update(['completed_at' => now()]);

        ProjectMember::where('project_id', $project->id)
            ->where('status', 'active')
            ->where('access_level', '<', $milestone->unlocks_access_level)
            ->update(['access_level' => $milestone->unlocks_access_level]);

        return back()->with(
            'success',
            "Milestone '{$milestone->title}' completed. Team access upgraded to level {$milestone->unlocks_access_level}."
        );
    }

    public function destroy(Request $request, Project $project, ProjectMilestone $milestone): RedirectResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);
        abort_if($milestone->project_id !== $project->id, 404);
        abort_if($milestone->completed_at !== null, 400);

        $milestone->delete();

        return back()->with('success', 'Milestone deleted');
    }
}
