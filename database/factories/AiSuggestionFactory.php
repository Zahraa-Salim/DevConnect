<?php

namespace Database\Factories;

use App\Models\AiSuggestion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiSuggestion>
 */
class AiSuggestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source_type' => fake()->randomElement(['project', 'contribution']),
            'source_id' => fake()->numberBetween(1, 100),
            'cv_text' => 'Contributed to a collaborative web project using modern PHP and Vue.js. Delivered high-quality features on schedule within an agile team.',
            'portfolio_text' => 'Built and shipped full-stack features for a real-world Lebanese developer platform. Demonstrated expertise in Laravel, Vue, and MySQL.',
            'linkedin_text' => 'Collaborated on DevConnect, a developer community platform. Led backend API development using Laravel 12 and contributed to the Vue 3 frontend.',
            'model' => 'claude-haiku-4-5-20251001',
            'tokens_used' => fake()->numberBetween(800, 1500),
        ];
    }
}
