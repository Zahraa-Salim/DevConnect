<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectPulseLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectPulseLog>
 * Note: project_pulse_log has no created_at/updated_at columns.
 * Use DB::table('project_pulse_log')->insert() in seeders instead of ::create().
 */
class ProjectPulseLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'signals' => [
                'active_members' => fake()->numberBetween(1, 5),
                'tasks_done' => fake()->numberBetween(0, 10),
                'last_activity_days' => fake()->numberBetween(0, 14),
            ],
            'status' => fake()->randomElement(['nudge_sent', 'at_risk', 'resolved']),
            'triggered_at' => now(),
        ];
    }
}
