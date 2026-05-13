<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'idea_id' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['practice', 'real_client']),
            'domain' => fake()->word(),
            'tech_stack' => ['PHP', 'Vue', 'Laravel'],
            'max_members' => fake()->numberBetween(3, 10),
            'estimated_duration' => fake()->word(),
            'status' => Project::STATUS_OPEN,
            'completed_at' => null,
        ];
    }
}
