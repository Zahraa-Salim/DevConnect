<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function actingAs(Authenticatable $user, $guard = null): static
    {
        if (method_exists($user, 'roleDiscoveryAnswers') && ! $user->roleDiscoveryAnswers()->exists()) {
            $user->roleDiscoveryAnswers()->create([
                'answers' => ['test' => true],
                'suggested_role' => $user->role ?? 'dev',
            ]);
        }

        if (method_exists($user, 'skills') && ! $user->bio && ! $user->skills()->exists()) {
            $user->skills()->create([
                'skill_name' => 'Testing',
                'proficiency' => 3,
            ]);
        }

        if (method_exists($user, 'workingStyle') && ! $user->workingStyle()->exists()) {
            $user->workingStyle()->create([
                'communication_pref' => 'async',
                'timezone' => 'Asia/Beirut',
            ]);
        }

        return parent::actingAs($user, $guard);
    }
}
