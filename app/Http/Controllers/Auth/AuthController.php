<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'role' => User::ROLE_EXPLORING,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        $redirect = $request->input('redirect');
        if ($this->isLocalRedirect($redirect)) {
            return redirect($redirect);
        }

        return redirect()->route('onboarding.quiz');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt(
            $request->only('email', 'password'),
            $request->boolean('remember')
        )) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        if (Auth::user()->suspended_at !== null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages([
                'email' => 'Your account has been suspended. Please contact support.',
            ]);
        }

        $redirect = $request->input('redirect');
        if ($this->isLocalRedirect($redirect)) {
            return redirect($redirect);
        }

        return redirect('/dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function isLocalRedirect(mixed $redirect): bool
    {
        return is_string($redirect)
            && str_starts_with($redirect, '/')
            && ! str_starts_with($redirect, '//');
    }
}
