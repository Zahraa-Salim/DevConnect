<?php

declare(strict_types=1);

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show()
    {
        return inertia('ProfileSetup');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'role' => ['nullable', 'in:dev,designer,pm,mentor,exploring'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:80'],
            'communication_pref' => ['nullable', 'in:async,sync,mixed'],
            'feedback_style' => ['nullable', 'in:direct,gentle,structured'],
            'conflict_approach' => ['nullable', 'in:discuss,vote,defer'],
            'weekly_hours' => ['nullable', 'integer', 'min:1', 'max:80'],
            'work_hours_start' => ['nullable', 'date_format:H:i'],
            'work_hours_end' => ['nullable', 'date_format:H:i'],
        ]);

        $user = Auth::user();

        DB::transaction(function () use ($user, $validated) {
            // Update user fields
            if (! empty($validated['name'])) {
                $user->name = $validated['name'];
            }
            if (array_key_exists('bio', $validated)) {
                $user->bio = $validated['bio'];
            }
            if (! empty($validated['role'])) {
                $user->role = $validated['role'];
            }
            $user->save();

            // Replace skills (simplest approach for v1)
            if (isset($validated['skills'])) {
                $user->skills()->delete();
                foreach ($validated['skills'] as $skillName) {
                    $user->skills()->create([
                        'skill_name' => $skillName,
                        'proficiency' => 3,
                    ]);
                }
            }

            // Upsert working style
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

        return redirect()->route('dashboard')->with('success', 'Profile saved.');
    }
}
