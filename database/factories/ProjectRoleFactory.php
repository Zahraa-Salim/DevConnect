<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectRole>
 */
class ProjectRoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'role_name' => fake()->jobTitle(),
            'slots' => fake()->numberBetween(1, 5),
            'description' => fake()->sentence(),
            'filled' => 0,
            'is_open' => true,
        ];
    }
}
