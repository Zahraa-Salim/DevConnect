<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserSkill;
use App\Models\WorkingStyle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// Seeds Sara Khalil plus reusable demo teammates and community members.
class DemoUserProfileSeeder extends Seeder
{
    public const SARA_EMAIL = 'sara.khalil@designco.io';

    public const TEAMMATES = [
        ['name' => 'Ahmad Nasser', 'email' => 'ahmad.nasser@demo.io', 'role' => 'dev'],
        ['name' => 'Lara Haddad', 'email' => 'lara.haddad@demo.io', 'role' => 'designer'],
        ['name' => 'Omar Fayad', 'email' => 'omar.fayad@demo.io', 'role' => 'pm'],
        ['name' => 'Maya Rizk', 'email' => 'maya.rizk@demo.io', 'role' => 'designer'],
    ];

    public function run(): void
    {
        $memberSince = Carbon::parse('2022-03-15 09:00:00');
        $demoPassword = Hash::make('demo1234');

        $sara = User::updateOrCreate(
            ['email' => self::SARA_EMAIL],
            [
                'name' => 'Sara Khalil',
                'password' => $demoPassword,
                'role' => User::ROLE_MENTOR,
                'bio' => 'Product designer with 8 years of experience in fintech and SaaS. Passionate about systems thinking and mentoring early-career designers. Based in Beirut, Lebanon.',
                'avatar_url' => 'https://api.dicebear.com/8.x/initials/svg?seed=Sara%20Khalil',
                'is_available' => true,
                'is_verified' => true,
                'reputation_score' => 92.40,
                'contribution_dna' => [
                    'label' => 'Design Systems Mentor',
                    'tasks_done' => 86,
                    'prs_merged' => 14,
                    'plan' => 'pro',
                    'location' => 'Beirut, Lebanon',
                ],
                'email_verified_at' => $memberSince,
                'created_at' => $memberSince,
                'updated_at' => Carbon::parse('2026-05-10 10:15:00'),
            ]
        );

        WorkingStyle::updateOrCreate(
            ['user_id' => $sara->id],
            [
                'communication_pref' => WorkingStyle::COMMUNICATION_MIXED,
                'work_hours_start' => '09:30:00',
                'work_hours_end' => '17:30:00',
                'timezone' => 'Asia/Beirut',
                'feedback_style' => WorkingStyle::FEEDBACK_STRUCTURED,
                'conflict_approach' => WorkingStyle::CONFLICT_DISCUSS,
                'weekly_hours' => 28,
            ]
        );

        foreach (['UX Design', 'Design Systems', 'Figma', 'User Research', 'Prototyping'] as $index => $skill) {
            UserSkill::updateOrCreate(
                ['user_id' => $sara->id, 'skill_name' => $skill],
                ['proficiency' => 5 - min($index, 2), 'is_endorsed' => true, 'endorsement_count' => 8 - $index]
            );
        }

        foreach (self::TEAMMATES as $index => $person) {
            $user = User::updateOrCreate(
                ['email' => $person['email']],
                [
                    'name' => $person['name'],
                    'password' => $demoPassword,
                    'role' => $person['role'],
                    'bio' => fake()->sentence(14),
                    'avatar_url' => 'https://api.dicebear.com/8.x/initials/svg?seed=' . urlencode($person['name']),
                    'is_available' => true,
                    'is_verified' => $index < 2,
                    'reputation_score' => 71 + ($index * 4),
                    'email_verified_at' => Carbon::parse('2023-02-01')->addMonths($index),
                    'created_at' => Carbon::parse('2023-02-01')->addMonths($index),
                    'updated_at' => Carbon::parse('2026-05-09')->subDays($index),
                ]
            );

            foreach ($this->skillsFor($person['role']) as $skill) {
                UserSkill::updateOrCreate(
                    ['user_id' => $user->id, 'skill_name' => $skill],
                    ['proficiency' => fake()->numberBetween(3, 5), 'is_endorsed' => true, 'endorsement_count' => fake()->numberBetween(2, 7)]
                );
            }
        }

        $communityUsers = [];
        for ($i = 1; $i <= 121; $i++) {
            $communityUsers[] = [
                'name' => fake()->name(),
                'email' => sprintf('demo.member%03d@demo.io', $i),
                'password' => $demoPassword,
                'role' => fake()->randomElement([User::ROLE_DEV, User::ROLE_DESIGNER, User::ROLE_PM, User::ROLE_EXPLORING]),
                'bio' => fake()->sentence(12),
                'avatar_url' => null,
                'is_available' => true,
                'is_verified' => $i % 5 === 0,
                'is_admin' => false,
                'reputation_score' => fake()->randomFloat(2, 20, 88),
                'email_verified_at' => Carbon::parse('2024-01-01')->addDays($i),
                'created_at' => Carbon::parse('2024-01-01')->addDays($i),
                'updated_at' => Carbon::parse('2026-04-01')->addDays($i % 30),
            ];
        }

        User::upsert(
            $communityUsers,
            ['email'],
            ['name', 'password', 'role', 'bio', 'avatar_url', 'is_available', 'is_verified', 'is_admin', 'reputation_score', 'email_verified_at', 'updated_at']
        );
    }

    private function skillsFor(string $role): array
    {
        return match ($role) {
            User::ROLE_DEV => ['Laravel', 'Vue.js', 'API Design'],
            User::ROLE_PM => ['Roadmapping', 'Stakeholder Management', 'Agile'],
            default => ['Figma', 'Product Design', 'User Research'],
        };
    }
}
