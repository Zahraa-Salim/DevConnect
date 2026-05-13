<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rating>
 */
class RatingFactory extends Factory
{
    public function definition(): array
    {
        $comm = fake()->numberBetween(1, 5);
        $rel  = fake()->numberBetween(1, 5);
        $contrib = fake()->numberBetween(1, 5);

        return [
            'project_id' => Project::factory(),
            'rater_id' => User::factory(),
            'rated_id' => User::factory(),
            'communication_score' => $comm,
            'reliability_score' => $rel,
            'contribution_score' => $contrib,
            'overall_score' => round(($comm + $rel + $contrib) / 3, 2),
            'comment' => fake()->optional()->sentence(),
        ];
    }
}
