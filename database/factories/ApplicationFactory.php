<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
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
            'user_id' => User::factory(),
            'role_id' => null,
            'message' => fake()->paragraph(),
            'status' => Application::STATUS_PENDING,
            'decided_by' => null,
            'reviewed_at' => null,
        ];
    }
}
