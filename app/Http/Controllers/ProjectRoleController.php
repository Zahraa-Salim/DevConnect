<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectRole;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ProjectRoleController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);

        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:100'],
            'slots' => ['required', 'integer', 'min:1', 'max:10'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $project->roles()->create([
            'role_name' => $validated['role_name'],
            'slots' => $validated['slots'],
            'description' => $validated['description'] ?? null,
            'filled' => 0,
            'is_open' => true,
        ]);

        return back()->with('success', 'Role created successfully!');
    }

    public function update(Request $request, Project $project, ProjectRole $role): RedirectResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);
        abort_if($role->project_id !== $project->id, 404);

        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:100'],
            'slots' => ['required', 'integer', 'min:1', 'max:10'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $role->update([
            'role_name' => $validated['role_name'],
            'slots' => $validated['slots'],
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Role updated successfully!');
    }

    public function destroy(Project $project, ProjectRole $role): RedirectResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);
        abort_if($role->project_id !== $project->id, 404);

        if ($role->filled > 0) {
            return back()->withErrors([
                'message' => 'Cannot delete a role with active members.',
            ]);
        }

        $role->delete();

        return back()->with('success', 'Role deleted successfully!');
    }
}
