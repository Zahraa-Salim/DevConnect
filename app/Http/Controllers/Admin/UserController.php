<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::query()->orderByDesc('created_at');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        return Inertia::render('Admin/Users/Index', [
            'users'   => $query->paginate(20)->withQueryString(),
            'filters' => $request->only('search', 'role'),
        ]);
    }

    public function suspend(User $user): RedirectResponse
    {
        $user->update(['suspended_at' => now()]);

        return redirect()->back()->with('success', "{$user->name} has been suspended.");
    }

    public function unsuspend(User $user): RedirectResponse
    {
        $user->update(['suspended_at' => null]);

        return redirect()->back()->with('success', "{$user->name} has been unsuspended.");
    }
}
