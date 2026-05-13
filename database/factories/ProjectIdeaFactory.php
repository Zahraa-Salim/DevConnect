<?php

namespace Database\Factories;

use App\Models\ProjectIdea;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectIdea>
 */
class ProjectIdeaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source' => fake()->randomElement(['ai', 'community', 'curated']),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'domain' => fake()->randomElement(['Web', 'AI', 'API', 'Mobile', 'Data']),
            'difficulty' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'team_size' => fake()->numberBetween(2, 5),
            'suggested_roles' => ['Backend Dev', 'Frontend Dev'],
            'requirements' => null,
            'submitted_by' => null,
            'status' => 'active',
            'upvotes' => 0,
            'comments_count' => 0,
            'converted_project_id' => null,
        ];
    }
}
