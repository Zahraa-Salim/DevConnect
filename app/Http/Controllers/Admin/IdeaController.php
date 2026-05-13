<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectIdea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IdeaController extends Controller
{
    public function index(): Response
    {
        $ideas = ProjectIdea::where('source', 'curated')
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Ideas/Index', [
            'ideas' => $ideas,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Ideas/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'domain' => ['nullable', 'string', 'max:80'],
            'difficulty' => ['required', 'in:beginner,intermediate,advanced'],
            'team_size' => ['nullable', 'integer', 'min:1', 'max:20'],
            'suggested_roles' => ['nullable', 'array'],
            'suggested_roles.*' => ['string', 'max:80'],
            'requirements' => ['nullable', 'array'],
            'requirements.*' => ['string', 'max:200'],
            'status' => ['required', 'in:active,featured,pending'],
        ]);

        ProjectIdea::create([
            ...$validated,
            'source' => 'curated',
            'submitted_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.ideas.index')
            ->with('success', 'Idea created.');
    }

    public function edit(ProjectIdea $idea): Response
    {
        return Inertia::render('Admin/Ideas/Edit', [
            'idea' => $idea,
        ]);
    }

    public function update(Request $request, ProjectIdea $idea): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'domain' => ['nullable', 'string', 'max:80'],
            'difficulty' => ['required', 'in:beginner,intermediate,advanced'],
            'team_size' => ['nullable', 'integer', 'min:1', 'max:20'],
            'suggested_roles' => ['nullable', 'array'],
            'suggested_roles.*' => ['string', 'max:80'],
            'requirements' => ['nullable', 'array'],
            'requirements.*' => ['string', 'max:200'],
            'status' => ['required', 'in:active,featured,pending,removed'],
        ]);

        $idea->update($validated);

        return redirect()
            ->route('admin.ideas.index')
            ->with('success', 'Idea updated.');
    }

    public function destroy(ProjectIdea $idea): RedirectResponse
    {
        $idea->delete();

        return redirect()
            ->route('admin.ideas.index')
            ->with('success', 'Idea deleted.');
    }
}
