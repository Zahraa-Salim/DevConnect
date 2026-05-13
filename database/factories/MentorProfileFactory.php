<?php

namespace Database\Factories;

use App\Models\MentorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MentorProfile>
 */
class MentorProfileFactory extends Factory
{
    public function definition(): array
    {
        $allTopics = ['Laravel', 'Vue', 'React', 'Python', 'Node', 'Career', 'TypeScript', 'Docker'];

        return [
            'user_id' => User::factory(),
            'motivation' => 'I want to help junior developers grow their skills and navigate the Lebanese tech scene.',
            'focus_areas' => fake()->randomElements($allTopics, 2),
            'availability' => ['monday' => true, 'wednesday' => true, 'friday' => true],
            'is_active' => true,
            'status' => 'approved',
            'experience_years' => fake()->numberBetween(1, 8),
            'topics' => fake()->randomElements($allTopics, fake()->numberBetween(2, 4)),
            'domains' => ['Web', 'API'],
            'hours_per_week' => fake()->randomElement([2, 3, 4, 5]),
            'github_score' => fake()->numberBetween(50, 90),
            'avg_rating' => 0.00,
            'sessions_completed' => 0,
            'projects_advised' => 0,
            'auto_approved_at' => now()->subMonths(3),
            'approved_at' => now()->subMonths(3),
        ];
    }
}
