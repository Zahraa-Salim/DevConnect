<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GitHubAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('github')
            ->scopes(['read:user', 'user:email'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $githubUser = Socialite::driver('github')->user();
        } catch (\Exception $e) {
            Log::error('GitHub OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect('/login')->withErrors([
                'github' => 'GitHub sign-in failed. Please try again or use email login.',
            ]);
        }

        if (Auth::check()) {
            $currentUser = Auth::user();

            $existingUser = User::where('github_username', $githubUser->getNickname())
                ->where('id', '!=', $currentUser->id)
                ->first();

            if ($existingUser) {
                $existingUser->update([
                    'github_username' => null,
                    'github_token' => null,
                    'github_synced_at' => null,
                ]);
            }

            try {
                $currentUser->update([
                    'github_username' => $githubUser->getNickname(),
                    'github_token' => $githubUser->token,
                    'github_synced_at' => now(),
                    'avatar_url' => $githubUser->getAvatar() ?? $currentUser->avatar_url,
                ]);
            } catch (UniqueConstraintViolationException $e) {
                return redirect()
                    ->route('onboarding.profile')
                    ->withErrors(['github' => 'This GitHub account is already linked to another user. Please contact support.']);
            }

            $hasProfile = $currentUser->workingStyle()->exists();

            return redirect()
                ->route($hasProfile ? 'dashboard' : 'onboarding.profile')
                ->with('success', 'GitHub connected successfully!');
        }

        $user = $request->user();

        if (! $user) {
            // Guest sign-in: look up by github_username first, then fall back to email.
            $user = User::where('github_username', $githubUser->getNickname())->first();

            if (! $user && $githubUser->getEmail()) {
                $user = User::where('email', $githubUser->getEmail())->first();
            }
        }

        $isNew = false;

        if (! $user) {
            $isNew = true;
            try {
                $user = User::create([
                    'name' => $githubUser->getName() ?? $githubUser->getNickname(),
                    'email' => $githubUser->getEmail()
                        ?? $githubUser->getNickname() . '@users.noreply.github.com',
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(32)),
                    'role' => User::ROLE_EXPLORING,
                    'github_username' => $githubUser->getNickname(),
                    'github_token' => $githubUser->token,
                    'github_synced_at' => now(),
                    'avatar_url' => $githubUser->getAvatar(),
                ]);
            } catch (UniqueConstraintViolationException $e) {
                return redirect()
                    ->route('login')
                    ->withErrors(['github' => 'This GitHub account is already linked to another user. Please contact support.']);
            }
        } else {
            // Existing user — refresh their GitHub data
            try {
                $user->update([
                    'github_username' => $githubUser->getNickname(),
                    'github_token' => $githubUser->token,
                    'github_synced_at' => now(),
                    'avatar_url' => $githubUser->getAvatar() ?? $user->avatar_url,
                ]);
            } catch (UniqueConstraintViolationException $e) {
                return redirect()
                    ->route('onboarding.profile')
                    ->withErrors(['github' => 'This GitHub account is already linked to another user. Please contact support.']);
            }
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $hasQuiz = $user->roleDiscoveryAnswers()->exists();
        $hasProfile = $user->workingStyle()->exists();

        if ($isNew || ! $hasQuiz) {
            return redirect()->route('onboarding.quiz');
        }

        if (! $hasProfile) {
            return redirect()->route('onboarding.profile');
        }

        return redirect()->route('dashboard');
    }
}
