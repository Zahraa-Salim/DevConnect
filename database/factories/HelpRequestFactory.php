<?php

namespace Database\Factories;

use App\Models\HelpRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HelpRequest>
 */
class HelpRequestFactory extends Factory
{
    private static array $techOptions = ['Laravel', 'Vue', 'React', 'Redis', 'MySQL', 'Docker', 'TypeScript'];

    public function definition(): array
    {
        $user = User::factory();

        return [
            'user_id' => $user,
            'project_id' => null,
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'tech_tags' => fake()->randomElements(self::$techOptions, 2),
            'status' => 'pending',
            'requester_id' => $user,
            'mentor_id' => User::factory(),
            'context' => fake()->paragraph(),
            'stack' => implode(', ', fake()->randomElements(self::$techOptions, 2)),
            'code_snippet' => null,
            'response' => null,
            'responded_at' => null,
        ];
    }
}
