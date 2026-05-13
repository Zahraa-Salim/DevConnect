<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Orchestrates all demo data for Sara Khalil in dependency order.
 *
 * Run with: php artisan db:seed --class=DemoUserSeeder
 *
 * Order matters:
 *  1. Users + skills + working styles
 *  2. Ideas (submitted by Sara)
 *  3. Projects + roles + memberships
 *  4. Tasks + sprints
 *  5. Conversations + messages
 *  6. Mentor profile + slots + bookings
 *  7. Notifications
 *  --- NEW SEEDERS ---
 *  8. Workspace data (decision log, files, pulse, ratings, endorsements, chemistry, AI suggestions)
 *  9. Contribution logs + GitHub issues (open issues for /contribute, Sara's merged PRs)
 * 10. Peer help board (community Q&A, formerly help requests)
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Original seeders (unchanged)
            DemoUserProfileSeeder::class,
            DemoIdeaSeeder::class,
            DemoProjectSeeder::class,
            DemoTaskSeeder::class,
            DemoConversationSeeder::class,
            DemoMentorSeeder::class,
            DemoNotificationSeeder::class,

            // New seeders — run after all original seeders
            DemoWorkspaceSeeder::class,
            DemoContributionSeeder::class,
            DemoPeerHelpSeeder::class,
        ]);

        $this->command?->info('');
        $this->command?->info('✅ Demo data seeded successfully.');
        $this->command?->info('');
        $this->command?->info('Login: sara.khalil@designco.io / demo1234');
        $this->command?->info('');
        $this->command?->info('What is now seeded per project:');
        $this->command?->info('  • Decision log (4–6 entries per project)');
        $this->command?->info('  • Project files (2–6 realistic files per project)');
        $this->command?->info('  • Alive signals (3–6 per member over last 10 days)');
        $this->command?->info('  • Project pulse log (4 entries per project)');
        $this->command?->info('  • Ratings + comments (completed projects only)');
        $this->command?->info('  • Skill endorsements (completed projects only)');
        $this->command?->info('  • Chemistry score (active + completed projects)');
        $this->command?->info('  • AI suggestions: CV, portfolio, LinkedIn (completed projects)');
        $this->command?->info('');
        $this->command?->info('Also seeded:');
        $this->command?->info('  • 25 open GitHub issues for /contribute page');
        $this->command?->info('  • Sara has 3 merged PRs + 1 PR submitted in progress');
        $this->command?->info('  • Teammates have 6 merged contributions on their profiles');
        $this->command?->info('  • 14 community Q&A posts (open, in-progress, resolved)');
        $this->command?->info('  • 2 questions from Sara, 2 answered by Sara as mentor');
        $this->command?->info('');
    }
}