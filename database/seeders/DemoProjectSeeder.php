<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectRole;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

// Seeds demo projects, project roles, and memberships for Sara and teammates.
class DemoProjectSeeder extends Seeder
{
    public const PROJECTS = [
        'Redesign Fintech Dashboard' => ['status' => Project::STATUS_ACTIVE, 'members' => 5, 'tasks' => 12, 'due' => '2026-06-20', 'type' => Project::TYPE_REAL_CLIENT, 'domain' => 'Product'],
        'AI Onboarding Flow' => ['status' => Project::STATUS_ACTIVE, 'members' => 3, 'tasks' => 8, 'due' => '2026-07-05', 'type' => Project::TYPE_PRACTICE, 'domain' => 'AI'],
        'Design System v2.0' => ['status' => Project::STATUS_ACTIVE, 'members' => 7, 'tasks' => 34, 'due' => '2026-08-01', 'type' => Project::TYPE_REAL_CLIENT, 'domain' => 'Design Systems'],
        'Mobile App Prototype' => ['status' => Project::STATUS_COMPLETED, 'members' => 2, 'tasks' => 6, 'due' => null, 'type' => Project::TYPE_PRACTICE, 'domain' => 'Mobile'],
        'Brand Identity Refresh' => ['status' => Project::STATUS_COMPLETED, 'members' => 4, 'tasks' => 21, 'due' => null, 'type' => Project::TYPE_PRACTICE, 'domain' => 'Brand'],
    ];

    public function run(): void
    {
        $sara = User::where('email', DemoUserProfileSeeder::SARA_EMAIL)->firstOrFail();
        $pool = User::whereIn('email', array_column(DemoUserProfileSeeder::TEAMMATES, 'email'))
            ->orWhere('email', 'like', 'demo.member%')
            ->orderBy('id')
            ->get();

        $projectIndex = 0;
        foreach (self::PROJECTS as $title => $meta) {
            $project = Project::updateOrCreate(
                ['title' => $title, 'owner_id' => $sara->id],
                [
                    'description' => $this->descriptionFor($title),
                    'status' => $meta['status'],
                    'type' => $meta['type'],
                    'domain' => $meta['domain'],
                    'tech_stack' => $this->stackFor($title),
                    'repo_url' => 'https://github.com/demo/' . str($title)->slug('-'),
                    'max_members' => $meta['members'],
                    'estimated_duration' => $meta['due'] ? 'Due ' . $meta['due'] : 'Completed project',
                    'completed_at' => $meta['status'] === Project::STATUS_COMPLETED ? Carbon::parse('2026-04-15')->addDays($projectIndex * 3) : null,
                    'created_at' => Carbon::parse('2026-03-05')->addDays($projectIndex * 6),
                    'updated_at' => Carbon::parse('2026-05-07')->subDays($projectIndex),
                ]
            );

            foreach (['Product Designer', 'Frontend Developer', 'Research Lead', 'Project Lead'] as $roleIndex => $roleName) {
                ProjectRole::updateOrCreate(
                    ['project_id' => $project->id, 'role_name' => $roleName],
                    [
                        'slots' => $roleName === 'Frontend Developer' ? 2 : 1,
                        'filled' => $roleIndex < 3 ? 1 : 0,
                        'is_open' => $meta['status'] !== Project::STATUS_COMPLETED && $roleIndex === 1,
                        'description' => "Own {$roleName} responsibilities for {$title}.",
                    ]
                );
            }

            $members = collect([$sara])->merge($pool->take(max(0, $meta['members'] - 1)));
            foreach ($members as $memberIndex => $member) {
                ProjectMember::updateOrCreate(
                    ['project_id' => $project->id, 'user_id' => $member->id],
                    [
                        'role' => $member->id === $sara->id ? 'Product Designer' : fake()->randomElement(['Frontend Developer', 'Research Lead', 'PM', 'Designer']),
                        'status' => ProjectMember::STATUS_ACTIVE,
                        'access_level' => $member->id === $sara->id ? 3 : fake()->numberBetween(1, 3),
                        'joined_at' => Carbon::parse('2026-03-10')->addDays($projectIndex + $memberIndex),
                        'left_at' => null,
                    ]
                );
            }

            $projectIndex++;
        }
    }

    private function descriptionFor(string $title): string
    {
        return match ($title) {
            'Redesign Fintech Dashboard' => 'A full redesign of a fintech analytics dashboard with clearer hierarchy, better empty states, and investor-ready reporting.',
            'AI Onboarding Flow' => 'A guided onboarding experience that helps new users understand AI recommendations without feeling overwhelmed.',
            'Design System v2.0' => 'A second-generation design system focused on tokens, component governance, accessibility, and faster product delivery.',
            'Mobile App Prototype' => 'A polished mobile prototype for testing core journeys with stakeholders and early users.',
            default => 'A brand refresh covering identity principles, UI applications, and launch-ready design assets.',
        };
    }

    private function stackFor(string $title): array
    {
        return match ($title) {
            'Redesign Fintech Dashboard' => ['Figma', 'Vue', 'Laravel', 'Design Tokens'],
            'AI Onboarding Flow' => ['Figma', 'User Research', 'Prompt UX'],
            'Design System v2.0' => ['Figma', 'Storybook', 'Tailwind', 'Accessibility'],
            'Mobile App Prototype' => ['Figma', 'Maze', 'Mobile UX'],
            default => ['Brand Strategy', 'Figma', 'Illustrator'],
        };
    }
}
