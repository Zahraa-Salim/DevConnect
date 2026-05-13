<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function show(): Response
    {
        $user = auth()->user();
        $user->load(['skills', 'workingStyle']);

        return Inertia::render('Settings/Index', [
            'profile' => [
                'name'            => $user->name,
                'bio'             => $user->bio ?? '',
                'role'            => $user->role ?? 'exploring',
                'github_username' => $user->github_username,
                'skills'          => $user->skills->pluck('skill_name')->values()->all(),
                'working_style'   => $user->workingStyle ? [
                    'communication_pref' => $user->workingStyle->communication_pref,
                    'feedback_style'     => $user->workingStyle->feedback_style,
                    'conflict_approach'  => $user->workingStyle->conflict_approach,
                    'weekly_hours'       => $user->workingStyle->weekly_hours,
                    'work_hours_start'   => $user->workingStyle->work_hours_start
                        ? substr($user->workingStyle->work_hours_start, 0, 5)
                        : '09:00',
                    'work_hours_end'     => $user->workingStyle->work_hours_end
                        ? substr($user->workingStyle->work_hours_end, 0, 5)
                        : '17:00',
                ] : null,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'bio'                => ['nullable', 'string', 'max:1000'],
            'role'               => ['nullable', 'in:dev,designer,pm,mentor,exploring'],
            'skills'             => ['nullable', 'array'],
            'skills.*'           => ['string', 'max:80'],
            'communication_pref' => ['nullable', 'in:async,sync,mixed'],
            'feedback_style'     => ['nullable', 'in:direct,gentle,structured'],
            'conflict_approach'  => ['nullable', 'in:discuss,vote,defer'],
            'weekly_hours'       => ['nullable', 'integer', 'min:1', 'max:80'],
            'work_hours_start'   => ['nullable', 'date_format:H:i'],
            'work_hours_end'     => ['nullable', 'date_format:H:i'],
        ]);

        $user = auth()->user();

        DB::transaction(function () use ($user, $validated) {
            $user->update([
                'name' => $validated['name'],
                'bio'  => $validated['bio'] ?? null,
                'role' => $validated['role'] ?? $user->role,
            ]);

            if (array_key_exists('skills', $validated)) {
                $user->skills()->delete();
                foreach (($validated['skills'] ?? []) as $skill) {
                    $user->skills()->create(['skill_name' => $skill, 'proficiency' => 3]);
                }
            }

            $stylePayload = array_filter([
                'communication_pref' => $validated['communication_pref'] ?? null,
                'feedback_style'     => $validated['feedback_style'] ?? null,
                'conflict_approach'  => $validated['conflict_approach'] ?? null,
                'weekly_hours'       => $validated['weekly_hours'] ?? null,
                'work_hours_start'   => $validated['work_hours_start'] ?? null,
                'work_hours_end'     => $validated['work_hours_end'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');

            if (! empty($stylePayload)) {
                $user->workingStyle()->updateOrCreate(
                    ['user_id' => $user->id],
                    $stylePayload
                );
            }
        });

        return back()->with('success', 'Settings saved.');
    }
}
