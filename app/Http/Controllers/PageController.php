<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function landing(): Response
    {
        return Inertia::render('Landing');
    }

    public function login(): Response
    {
        return Inertia::render('Login');
    }

    public function register(): Response
    {
        return Inertia::render('Register');
    }

    public function profileRedirect(): RedirectResponse
    {
        return redirect()->route('profile.show', auth()->id());
    }
}
