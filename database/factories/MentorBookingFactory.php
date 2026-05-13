<?php

namespace Database\Factories;

use App\Models\MentorBooking;
use App\Models\MentorSlot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MentorBooking>
 */
class MentorBookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mentor_slot_id' => MentorSlot::factory(),
            'student_id' => User::factory(),
            'topic' => fake()->sentence(6),
            'cancellation_reason' => null,
        ];
    }
}
