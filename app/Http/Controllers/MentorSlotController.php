<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MentorBooking;
use App\Models\MentorSlot;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\BookingConfirmedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MentorSlotController extends Controller
{
    public function book(Request $request, MentorSlot $slot): JsonResponse
    {
        abort_if($slot->status !== MentorSlot::STATUS_AVAILABLE, 422, 'This slot is no longer available.');

        $hasOverlap = MentorBooking::where('student_id', auth()->id())
            ->whereHas('slot', function ($q) use ($slot) {
                $q->where('starts_at', '<', $slot->ends_at)
                  ->where('ends_at', '>', $slot->starts_at);
            })
            ->exists();

        abort_if($hasOverlap, 422, 'You already have a booking overlapping this time slot.');

        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:500'],
        ]);

        MentorBooking::create([
            'mentor_slot_id' => $slot->id,
            'student_id'     => auth()->id(),
            'topic'          => $validated['topic'],
        ]);

        $slot->update(['status' => MentorSlot::STATUS_BOOKED]);

        $slot->load('mentorProfile.user');
        $mentorUser = $slot->mentorProfile?->user;

        if ($mentorUser) {
            $mentorUser->notify(new BookingConfirmedNotification());
        }
        auth()->user()->notify(new BookingConfirmedNotification());

        return response()->json(['success' => true]);
    }

    public function cancel(Request $request, MentorSlot $slot): JsonResponse
    {
        $slot->load('booking.student', 'mentorProfile.user');
        $booking = $slot->booking;

        abort_if(! $booking, 404, 'No booking found for this slot.');

        $authId    = auth()->id();
        $studentId = $booking->student_id;
        $mentorId  = $slot->mentorProfile?->user_id;

        abort_if($authId !== $studentId && $authId !== $mentorId, 403, 'Not authorised to cancel this booking.');

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ]);

        $booking->update(['cancellation_reason' => $validated['cancellation_reason']]);
        $slot->update(['status' => MentorSlot::STATUS_AVAILABLE]);

        // Notify the other party
        if ($authId === $studentId && $slot->mentorProfile?->user) {
            $slot->mentorProfile->user->notify(new BookingCancelledNotification());
        } elseif ($authId === $mentorId && $booking->student) {
            $booking->student->notify(new BookingCancelledNotification());
        }

        return response()->json(['success' => true]);
    }
}
