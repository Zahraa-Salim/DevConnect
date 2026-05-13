<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $roles = ['dev', 'designer', 'pm', 'mentor', 'exploring'];
        $username = strtolower(preg_replace('/[^a-z0-9\-]/', '', str_replace([' ', '_', '.'], '-', fake()->unique()->userName())));

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'github_username' => $username ?: 'user' . fake()->unique()->numberBetween(1000, 99999),
            'avatar_url' => 'https://avatars.githubusercontent.com/u/' . fake()->numberBetween(1000, 9999999),
            'bio' => fake()->sentence(),
            'role' => fake()->randomElement($roles),
            'is_available' => true,
            'is_verified' => false,
            'reputation_score' => fake()->numberBetween(0, 75),
            'is_admin' => false,
            'contribution_dna' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
