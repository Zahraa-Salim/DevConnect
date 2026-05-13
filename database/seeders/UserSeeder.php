<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private array $skills = [
        'Laravel', 'Vue', 'React', 'Python', 'MySQL',
        'Figma', 'Tailwind', 'Node', 'TypeScript', 'Docker', 'Redis', 'Git',
    ];

    public function run(): void
    {
        // --- Named users ---
        $admin = User::create([
            'name'             => 'Admin DevConnect',
            'email'            => 'admin@devconnect.lb',
            'password'         => Hash::make('password'),
            'role'             => 'dev',
            'github_username'  => 'devconnect-admin',
            'avatar_url'       => 'https://avatars.githubusercontent.com/u/1000001',
            'is_admin'         => true,
            'is_verified'      => true,
            'reputation_score' => 95,
            'email_verified_at' => now(),
        ]);

        $rami = User::create([
            'name'             => 'Rami Haddad',
            'email'            => 'rami@devconnect.lb',
            'password'         => Hash::make('password'),
            'role'             => 'dev',
            'github_username'  => 'ramihaddad',
            'avatar_url'       => 'https://avatars.githubusercontent.com/u/2000002',
            'is_admin'         => false,
            'is_verified'      => true,
            'reputation_score' => 82,
            'email_verified_at' => now(),
            'contribution_dna' => [
                'label'               => 'Builder',
                'tasks_done'          => 14,
                'prs_merged'          => 3,
                'projects_completed'  => 2,
                'updated_at'          => '2026-03-01',
            ],
        ]);

        $lara = User::create([
            'name'             => 'Lara Khoury',
            'email'            => 'lara@devconnect.lb',
            'password'         => Hash::make('password'),
            'role'             => 'dev',
            'github_username'  => 'larakhoury',
            'avatar_url'       => 'https://avatars.githubusercontent.com/u/3000003',
            'is_admin'         => false,
            'is_verified'      => true,
            'reputation_score' => 71,
            'email_verified_at' => now(),
            'contribution_dna' => [
                'label'               => 'Contributor',
                'tasks_done'          => 9,
                'prs_merged'          => 5,
                'projects_completed'  => 2,
                'updated_at'          => '2026-03-01',
            ],
        ]);

        $tarek = User::create([
            'name'             => 'Tarek Nassar',
            'email'            => 'tarek@devconnect.lb',
            'password'         => Hash::make('password'),
            'role'             => 'designer',
            'github_username'  => 'tareknassar',
            'avatar_url'       => 'https://avatars.githubusercontent.com/u/4000004',
            'is_admin'         => false,
            'is_verified'      => true,
            'reputation_score' => 60,
            'email_verified_at' => now(),
        ]);

        $maya = User::create([
            'name'             => 'Maya Frem',
            'email'            => 'maya@devconnect.lb',
            'password'         => Hash::make('password'),
            'role'             => 'pm',
            'github_username'  => 'mayafrem',
            'avatar_url'       => 'https://avatars.githubusercontent.com/u/5000005',
            'is_admin'         => false,
            'is_verified'      => false,
            'reputation_score' => 55,
            'email_verified_at' => now(),
        ]);

        // --- Factory users 6–12 ---
        $factoryConfig = [
            6  => ['name' => 'Karim Youssef',    'role' => 'dev',      'is_verified' => true,  'score' => 68],
            7  => ['name' => 'Nour Saad',         'role' => 'dev',      'is_verified' => true,  'score' => 52],
            8  => ['name' => 'Joelle Abi Nader',  'role' => 'dev',      'is_verified' => true,  'score' => 45],
            9  => ['name' => 'Elie Charbel',      'role' => 'dev',      'is_verified' => true,  'score' => 38],
            10 => ['name' => 'Sara Moussa',       'role' => 'designer', 'is_verified' => false, 'score' => 30],
            11 => ['name' => 'Georges Karam',     'role' => 'pm',       'is_verified' => false, 'score' => 25],
            12 => ['name' => 'Dani Rizk',         'role' => 'dev',      'is_verified' => false, 'score' => 18],
        ];

        $factoryUsers = [];
        foreach ($factoryConfig as $i => $cfg) {
            $factoryUsers[$i] = User::factory()->create([
                'name'             => $cfg['name'],
                'email'            => "user{$i}@devconnect.lb",
                'github_username'  => "devlb{$i}",
                'avatar_url'       => 'https://avatars.githubusercontent.com/u/' . (6000000 + $i),
                'role'             => $cfg['role'],
                'is_verified'      => $cfg['is_verified'],
                'reputation_score' => $cfg['score'],
                'is_admin'         => false,
                'password'         => Hash::make('password'),
            ]);
        }

        // --- Onboarding + skills for every user ---
        $allUsers = array_merge([$admin, $rami, $lara, $tarek, $maya], array_values($factoryUsers));

        foreach ($allUsers as $user) {
            $this->createOnboarding($user);
            $this->createSkills($user);
        }
    }

    private function createOnboarding(User $user): void
    {
        DB::table('role_discovery_answers')->insert([
            'user_id'        => $user->id,
            'answers'        => json_encode(['q1' => 'build', 'q2' => 'collaborate', 'q3' => 'team']),
            'suggested_role' => $user->role,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        DB::table('working_styles')->insert([
            'user_id'            => $user->id,
            'communication_pref' => fake()->randomElement(['async', 'sync', 'mixed']),
            'feedback_style'     => fake()->randomElement(['direct', 'gentle', 'structured']),
            'conflict_approach'  => fake()->randomElement(['discuss', 'vote', 'defer']),
            'weekly_hours'       => fake()->randomElement([10, 15, 20]),
            'timezone'           => 'Asia/Beirut',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    private function createSkills(User $user): void
    {
        $shuffled = collect($this->skills)->shuffle()->take(fake()->numberBetween(2, 4));
        foreach ($shuffled as $skill) {
            DB::table('user_skills')->insert([
                'user_id'          => $user->id,
                'skill_name'       => $skill,
                'proficiency'      => fake()->numberBetween(2, 5),
                'is_endorsed'      => false,
                'endorsement_count' => 0,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }
}
