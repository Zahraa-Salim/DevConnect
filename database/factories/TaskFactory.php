<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(['todo', 'in_progress', 'done']);

        return [
            'project_id' => Project::factory(),
            'sprint_id' => null,
            'assigned_to' => null,
            'parent_task_id' => null,
            'role_tag' => fake()->randomElement(['Backend', 'Frontend', 'Design', 'DevOps', null]),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'energy' => fake()->randomElement(['quick_win', 'deep_work', 'blocked']),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'status' => $status,
            'position' => fake()->numberBetween(0, 10),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'completed_at' => $status === 'done' ? now() : null,
            'story_points' => fake()->optional()->numberBetween(1, 8),
        ];
    }
}
