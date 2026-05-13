<?php

namespace Database\Factories;

use App\Models\MentorProfile;
use App\Models\MentorSlot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MentorSlot>
 */
class MentorSlotFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 day', '+30 days');
        $endsAt   = (clone $startsAt)->modify('+1 hour');

        return [
            'mentor_profile_id' => MentorProfile::factory(),
            'mentor_id' => User::factory(),
            'slot_datetime' => $startsAt,
            'is_booked' => false,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'available',
        ];
    }
}
