<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MentorProfile;
use App\Notifications\MentorApprovedNotification;
use App\Notifications\MentorPendingNotification;
use App\Notifications\MentorRejectedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MentorAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $query = MentorProfile::with('user')->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return Inertia::render('Admin/Mentors/Index', [
            'mentors' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only('status'),
        ]);
    }

    public function approve(MentorProfile $mentor): RedirectResponse
    {
        $mentor->update([
            'status'      => MentorProfile::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $mentor->user?->notify(new MentorApprovedNotification());

        return redirect()->back()->with('success', 'Mentor approved.');
    }

    public function reject(MentorProfile $mentor): RedirectResponse
    {
        $mentor->update([
            'status'      => MentorProfile::STATUS_REJECTED,
            'rejected_at' => now(),
        ]);

        $mentor->user?->notify(new MentorRejectedNotification());

        return redirect()->back()->with('success', 'Mentor application rejected.');
    }
}
