<?php

namespace Database\Factories;

use App\Models\DecisionLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DecisionLog>
 */
class DecisionLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'decision' => fake()->sentence(8),
            'reason' => fake()->sentence(12),
        ];
    }
}
