<?php

declare(strict_types=1);

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    public function show()
    {
        return inertia('RoleQuiz');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'suggested_role' => ['required', 'string', 'in:dev,designer,pm,exploring'],
        ]);

        $user = Auth::user();

        $user->roleDiscoveryAnswers()->create([
            'answers' => $validated['answers'],
            'suggested_role' => $validated['suggested_role'],
        ]);

        // Update user's primary role unless they pick exploring
        if ($validated['suggested_role'] !== User::ROLE_EXPLORING) {
            $user->role = $validated['suggested_role'];
            $user->save();
        }

        return redirect()->route('onboarding.profile');
    }
}
