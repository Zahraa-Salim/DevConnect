<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HelpRequest;
use App\Models\MentorProfile;
use App\Models\MentorSlot;
use App\Models\User;
use App\Notifications\MentorApprovedNotification;
use App\Notifications\MentorPendingNotification;
use App\Services\GitHubMentorScoringService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MentorController extends Controller
{
    public function apply(): Response|RedirectResponse
    {
        if (auth()->user()->mentorProfile) {
            return redirect()->route('mentor.dashboard');
        }

        return Inertia::render('Mentor/Apply');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'motivation'   => ['required', 'string', 'min:100'],
            'focus_areas'  => ['required', 'array', 'min:1'],
            'availability' => ['required', 'array'],
        ]);

        $user  = auth()->user();
        $score = app(GitHubMentorScoringService::class)->score($user);

        $status     = $score >= 60 ? MentorProfile::STATUS_APPROVED : MentorProfile::STATUS_PENDING;
        $approvedAt = $score >= 60 ? now() : null;

        MentorProfile::create([
            'user_id'      => $user->id,
            'motivation'   => $validated['motivation'],
            'focus_areas'  => $validated['focus_areas'],
            'availability' => $validated['availability'],
            'github_score' => $score,
            'status'       => $status,
            'approved_at'  => $approvedAt,
            'is_active'    => true,
        ]);

        if ($score >= 60) {
            $user->notify(new MentorApprovedNotification());
        } else {
            User::where('is_admin', true)->get()
                ->each(fn(User $admin) => $admin->notify(new MentorPendingNotification()));
        }

        return redirect()->route('mentor.dashboard')
            ->with('approved', $score >= 60);
    }

    public function dashboard(): Response
    {
        $user    = auth()->user();
        $profile = $user->mentorProfile;

        abort_if(! $profile, 403);

        $slots = MentorSlot::where('mentor_profile_id', $profile->id)
            ->where('status', MentorSlot::STATUS_BOOKED)
            ->where('starts_at', '>', now())
            ->with('booking.student')
            ->orderBy('starts_at')
            ->get();

        $focusAreas = $profile->focus_areas ?? [];

        $helpRequests = HelpRequest::where('status', HelpRequest::STATUS_OPEN)
            ->with('user')
            ->get()
            ->filter(function (HelpRequest $req) use ($focusAreas) {
                return ! empty(array_intersect($req->tech_tags ?? [], $focusAreas));
            })
            ->values();

        return Inertia::render('Mentor/Dashboard', [
            'mentor_profile' => $profile,
            'slots'          => $slots,
            'help_requests'  => $helpRequests,
        ]);
    }

    public function availability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'availability' => ['required', 'array'],
        ]);

        $profile = auth()->user()->mentorProfile;
        abort_if(! $profile, 403);

        $profile->update(['availability' => $validated['availability']]);

        MentorSlot::where('mentor_profile_id', $profile->id)
            ->where('status', MentorSlot::STATUS_AVAILABLE)
            ->where('starts_at', '>', now())
            ->delete();

        $this->generateSlots($profile, $validated['availability']);

        return response()->json(['success' => true]);
    }

    public function directory(Request $request): Response
    {
        $query = MentorProfile::where('status', MentorProfile::STATUS_APPROVED)
            ->where('is_active', true)
            ->with([
                'user',
                'slots' => fn($q) => $q->where('status', MentorSlot::STATUS_AVAILABLE)
                    ->where('starts_at', '>', now())
                    ->orderBy('starts_at')
                    ->limit(14),
            ])
            ->withCount([
                'slots as upcoming_slots_count' => fn($q) => $q
                    ->where('status', MentorSlot::STATUS_AVAILABLE)
                    ->where('starts_at', '>', now()),
            ]);

        if ($area = $request->query('area')) {
            $query->whereJsonContains('focus_areas', $area);
        }

        return Inertia::render('Mentor/Directory', [
            'mentors' => $query->get(),
        ]);
    }

    private function generateSlots(MentorProfile $profile, array $availability): void
    {
        $dayMap = [
            'mon' => Carbon::MONDAY,
            'tue' => Carbon::TUESDAY,
            'wed' => Carbon::WEDNESDAY,
            'thu' => Carbon::THURSDAY,
            'fri' => Carbon::FRIDAY,
            'sat' => Carbon::SATURDAY,
            'sun' => Carbon::SUNDAY,
        ];

        $today = Carbon::today();

        for ($i = 0; $i < 28; $i++) {
            $date    = $today->copy()->addDays($i);
            $dayOfWeek = $date->dayOfWeek; // 0=Sun … 6=Sat

            foreach ($dayMap as $key => $carbonDay) {
                if ($dayOfWeek !== $carbonDay) {
                    continue;
                }
                if (empty($availability[$key])) {
                    continue;
                }

                foreach ($availability[$key] as $hour) {
                    $hour = (int) $hour;
                    MentorSlot::create([
                        'mentor_profile_id' => $profile->id,
                        'starts_at'         => $date->copy()->setTime($hour, 0),
                        'ends_at'           => $date->copy()->setTime($hour + 1, 0),
                        'status'            => MentorSlot::STATUS_AVAILABLE,
                    ]);
                }
            }
        }
    }
}
