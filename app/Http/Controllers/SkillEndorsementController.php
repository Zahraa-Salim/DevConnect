<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\SkillEndorsement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SkillEndorsementController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'endorsements'               => ['required', 'array', 'min:1'],
            'endorsements.*.endorsed_id' => ['required', 'integer', 'exists:users,id'],
            'endorsements.*.skills'      => ['required', 'array', 'min:1', 'max:5'],
            'endorsements.*.skills.*'    => ['string', 'max:30'],
        ]);

        $authId = auth()->id();

        abort_unless(
            $project->members()->where('user_id', $authId)->where('status', 'active')->exists(),
            403,
            'You are not an active member of this project.'
        );

        foreach ($validated['endorsements'] as $entry) {
            $endorsedId = (int) $entry['endorsed_id'];

            if ($endorsedId === $authId) {
                continue;
            }

            $alreadyEndorsed = SkillEndorsement::where('project_id', $project->id)
                ->where('endorser_id', $authId)
                ->where('endorsed_id', $endorsedId)
                ->exists();

            if ($alreadyEndorsed) {
                continue;
            }

            foreach ($entry['skills'] as $skill) {
                SkillEndorsement::firstOrCreate([
                    'project_id'  => $project->id,
                    'endorser_id' => $authId,
                    'endorsed_id' => $endorsedId,
                    'skill_name'  => $skill,
                ]);
            }
        }

        return redirect()
            ->route('projects.show', $project->id)
            ->with('success', 'Endorsements saved.');
    }
}
