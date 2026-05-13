<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo user data is intentionally independent. Run it with:
        // php artisan db:seed --class=DemoUserSeeder
        $this->call([
            UserSeeder::class,
            ProjectIdeaSeeder::class,
            ProjectSeeder::class,
            ContributionLogSeeder::class,
            MentorSeeder::class,
            HelpRequestSeeder::class,
            NotificationSeeder::class,
            ReputationSeeder::class,
        ]);
    }
}
