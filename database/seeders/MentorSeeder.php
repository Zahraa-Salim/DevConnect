<?php

namespace Database\Seeders;

use App\Models\MentorBooking;
use App\Models\MentorProfile;
use App\Models\MentorProject;
use App\Models\MentorSlot;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class MentorSeeder extends Seeder
{
    public function run(): void
    {
        $lara  = User::where('email', 'lara@devconnect.lb')->first();
        $u6    = User::where('email', 'user6@devconnect.lb')->first();
        $u7    = User::where('email', 'user7@devconnect.lb')->first();
        $p5    = Project::where('title', 'NajahEd — LMS for Lebanese Schools')->first();

        // --- Lara's mentor profile ---
        $laraProfile = MentorProfile::create([
            'user_id'         => $lara->id,
            'motivation'      => 'I want to share my experience with junior Lebanese developers and help them navigate both technical challenges and career growth.',
            'focus_areas'     => ['Laravel', 'Vue', 'Career'],
            'availability'    => ['monday' => true, 'wednesday' => true, 'friday' => true],
            'is_active'       => true,
            'status'          => 'approved',
            'experience_years' => 3,
            'topics'          => ['Laravel', 'Vue', 'Career'],
            'domains'         => ['Web', 'EdTech'],
            'hours_per_week'  => 5,
            'github_score'    => 78,
            'avg_rating'      => 0.00,
            'sessions_completed' => 0,
            'projects_advised'   => 0,
            'auto_approved_at'   => now()->parse('2026-02-01'),
            'approved_at'        => now()->parse('2026-02-01'),
        ]);

        // Lara's slots — 4 future available
        $this->createSlot($laraProfile->id, $lara->id, now()->next('Monday')->setTime(10, 0), 'available');
        $this->createSlot($laraProfile->id, $lara->id, now()->next('Monday')->setTime(14, 0), 'available');
        $this->createSlot($laraProfile->id, $lara->id, now()->next('Wednesday')->setTime(10, 0), 'available');
        $this->createSlot($laraProfile->id, $lara->id, now()->next('Friday')->setTime(10, 0), 'available');

        // Lara's booked slot
        $bookedSlot = $this->createSlot($laraProfile->id, $lara->id, now()->next('Tuesday')->setTime(10, 0), 'booked');
        MentorBooking::create([
            'mentor_slot_id' => $bookedSlot->id,
            'student_id'     => $u7->id,
            'topic'          => 'Help with Laravel queues',
        ]);

        // Attach Lara as mentor to Project 5
        MentorProject::create([
            'mentor_id'  => $lara->id,
            'project_id' => $p5->id,
            'status'     => 'accepted',
            'joined_at'  => now(),
        ]);

        // --- User6's mentor profile ---
        $u6Profile = MentorProfile::create([
            'user_id'         => $u6->id,
            'motivation'      => 'Happy to help other developers level up their React and Python skills based on my hands-on project experience.',
            'focus_areas'     => ['React', 'Python'],
            'availability'    => ['tuesday' => true, 'thursday' => true],
            'is_active'       => true,
            'status'          => 'approved',
            'experience_years' => 2,
            'topics'          => ['React', 'Python'],
            'domains'         => ['Web', 'AI'],
            'hours_per_week'  => 3,
            'github_score'    => 65,
            'avg_rating'      => 0.00,
            'sessions_completed' => 0,
            'projects_advised'   => 0,
            'auto_approved_at'   => now()->parse('2026-02-15'),
            'approved_at'        => now()->parse('2026-02-15'),
        ]);

        // User6's 3 available future slots
        $this->createSlot($u6Profile->id, $u6->id, now()->next('Tuesday')->setTime(11, 0), 'available');
        $this->createSlot($u6Profile->id, $u6->id, now()->next('Thursday')->setTime(11, 0), 'available');
        $this->createSlot($u6Profile->id, $u6->id, now()->next('Tuesday')->setTime(15, 0), 'available');
    }

    private function createSlot(int $profileId, int $mentorUserId, \Carbon\Carbon $startsAt, string $status): MentorSlot
    {
        $endsAt = (clone $startsAt)->addHour();

        return MentorSlot::create([
            'mentor_profile_id' => $profileId,
            'mentor_id'         => $mentorUserId,
            'slot_datetime'     => $startsAt,
            'is_booked'         => $status === 'booked',
            'starts_at'         => $startsAt,
            'ends_at'           => $endsAt,
            'status'            => $status,
        ]);
    }
}
