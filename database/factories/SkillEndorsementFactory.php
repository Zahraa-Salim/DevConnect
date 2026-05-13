<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\SkillEndorsement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkillEndorsement>
 */
class SkillEndorsementFactory extends Factory
{
    private static array $skills = [
        'Laravel', 'Vue', 'React', 'Python', 'MySQL',
        'Figma', 'Tailwind', 'Node', 'TypeScript', 'Docker', 'Redis', 'Git',
    ];

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'endorser_id' => User::factory(),
            'endorsed_id' => User::factory(),
            'skill_name' => fake()->randomElement(self::$skills),
        ];
    }
}
