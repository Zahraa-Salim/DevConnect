<?php

namespace Database\Seeders;

use App\Models\IdeaComment;
use App\Models\ProjectIdea;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

// Seeds Sara-authored demo ideas with realistic comments from teammates.
class DemoIdeaSeeder extends Seeder
{
    public function run(): void
    {
        $sara = User::where('email', DemoUserProfileSeeder::SARA_EMAIL)->firstOrFail();
        $commenters = User::whereIn('email', array_column(DemoUserProfileSeeder::TEAMMATES, 'email'))->get();

        $ideas = [
            ['AI-powered design critique tool', 'Product', 47, 8, ProjectIdea::STATUS_FEATURED, 'An assistant that reviews flows, hierarchy, accessibility, and design system consistency before design crit.'],
            ['Async mentor feedback threads', 'Community', 31, 5, ProjectIdea::STATUS_PENDING, 'Structured mentor feedback spaces where juniors can ask follow-up questions across time zones.'],
            ['Weekly design challenge hub', 'Learning', 24, 12, ProjectIdea::STATUS_ACTIVE, 'A weekly challenge board with briefs, examples, peer review, and badges for consistent practice.'],
            ['Portfolio review marketplace', 'Marketplace', 19, 3, ProjectIdea::STATUS_ACTIVE, 'A trusted marketplace for designers to book focused portfolio reviews with vetted mentors.'],
        ];

        foreach ($ideas as $index => [$title, $domain, $upvotes, $commentCount, $status, $description]) {
            $idea = ProjectIdea::updateOrCreate(
                ['title' => $title, 'submitted_by' => $sara->id],
                [
                    'source' => ProjectIdea::SOURCE_COMMUNITY,
                    'description' => $description,
                    'domain' => $domain,
                    'difficulty' => fake()->randomElement([ProjectIdea::DIFFICULTY_BEGINNER, ProjectIdea::DIFFICULTY_INTERMEDIATE]),
                    'team_size' => fake()->numberBetween(3, 5),
                    'suggested_roles' => ['Product Designer', 'Frontend Developer', 'Research Lead'],
                    'requirements' => ['Clear brief', 'Prototype', 'Community feedback loop'],
                    'status' => $status,
                    'upvotes' => $upvotes,
                    'comments_count' => $commentCount,
                    'created_at' => Carbon::parse('2026-04-10')->addDays($index * 4),
                    'updated_at' => Carbon::parse('2026-05-05')->addDays($index),
                ]
            );

            foreach ($this->commentsFor($title, $commentCount) as $commentIndex => $body) {
                $user = $commenters[$commentIndex % max(1, $commenters->count())];
                IdeaComment::firstOrCreate(
                    [
                        'idea_id' => $idea->id,
                        'user_id' => $user->id,
                        'body' => $body,
                    ],
                    [
                        'created_at' => Carbon::parse('2026-04-20 10:00:00')->addHours($commentIndex * 7)->addDays($index),
                        'updated_at' => Carbon::parse('2026-04-20 10:00:00')->addHours($commentIndex * 7)->addDays($index),
                    ]
                );
            }
        }
    }

    private function commentsFor(string $title, int $count): array
    {
        $base = [
            "This would save a lot of time during early concept reviews.",
            "I like that this is specific enough to become a real workflow.",
            "Could we add templates so beginners know what good feedback looks like?",
            "This feels very useful for remote teams.",
            "The moderation model would matter here, but the idea is strong.",
            "I would use this with my students during weekly critiques.",
            "Maybe add a lightweight scoring rubric to keep feedback consistent.",
            "This could pair nicely with project portfolios.",
            "Love the focus on practical outcomes instead of generic advice.",
            "A version for teams would be interesting too.",
            "This is exactly the kind of tool juniors ask for.",
            "I can imagine a strong community loop around this.",
        ];

        return array_slice($base, 0, $count);
    }
}
