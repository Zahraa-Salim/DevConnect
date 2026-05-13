<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($request->routeIs('onboarding.*') || $request->routeIs('logout') || $request->routeIs('auth.github*')) {
            return $next($request);
        }

        if (! $user->roleDiscoveryAnswers()->exists()) {
            return redirect()->route('onboarding.quiz');
        }

        if (! $user->workingStyle()->exists()) {
            return redirect()->route('onboarding.profile');
        }

        return $next($request);
    }
}
