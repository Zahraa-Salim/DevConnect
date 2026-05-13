<?php

namespace Database\Seeders;

use App\Models\ProjectIdea;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectIdeaSeeder extends Seeder
{
    public function run(): void
    {
        $u6 = User::where('email', 'user6@devconnect.lb')->first();

        $idea1 = ProjectIdea::create([
            'source'      => 'curated',
            'title'       => 'LebanonWeather API',
            'description' => 'An open REST API providing real-time and historical weather data for Lebanese cities, built with Laravel and cached via Redis.',
            'domain'      => 'API',
            'difficulty'  => 'beginner',
            'team_size'   => 3,
            'suggested_roles' => ['Backend Dev', 'DevOps'],
            'submitted_by' => null,
            'status'       => 'active',
            'upvotes'      => 12,
        ]);

        $idea2 = ProjectIdea::create([
            'source'      => 'community',
            'title'       => 'Arabic OCR Tool',
            'description' => 'A machine-learning-powered tool that extracts and digitises text from Arabic documents and handwritten notes with high accuracy.',
            'domain'      => 'AI',
            'difficulty'  => 'intermediate',
            'team_size'   => 4,
            'suggested_roles' => ['ML Engineer', 'Backend Dev', 'Frontend Dev'],
            'submitted_by' => $u6->id,
            'status'       => 'active',
            'upvotes'      => 7,
        ]);

        $idea3 = ProjectIdea::create([
            'source'      => 'ai',
            'title'       => 'Dev Event Aggregator for Beirut',
            'description' => 'A website that aggregates and displays upcoming developer meetups, hackathons, and workshops happening across Beirut and Lebanon.',
            'domain'      => 'Web',
            'difficulty'  => 'beginner',
            'team_size'   => 3,
            'suggested_roles' => ['Frontend Dev', 'Backend Dev'],
            'submitted_by' => null,
            'status'       => 'active',
            'upvotes'      => 3,
        ]);

        // Idea votes
        $rami  = User::where('email', 'rami@devconnect.lb')->first();
        $lara  = User::where('email', 'lara@devconnect.lb')->first();
        $tarek = User::where('email', 'tarek@devconnect.lb')->first();
        $maya  = User::where('email', 'maya@devconnect.lb')->first();
        $u7    = User::where('email', 'user7@devconnect.lb')->first();

        // Idea 1 votes: users 2, 3, 4 (rami, lara, tarek)
        foreach ([$rami, $lara, $tarek] as $voter) {
            DB::table('idea_votes')->insert([
                'idea_id'    => $idea1->id,
                'user_id'    => $voter->id,
                'created_at' => now(),
            ]);
        }

        // Idea 2 votes: users 5, 6 (maya, u6)
        foreach ([$maya, $u6] as $voter) {
            DB::table('idea_votes')->insert([
                'idea_id'    => $idea2->id,
                'user_id'    => $voter->id,
                'created_at' => now(),
            ]);
        }
    }
}
