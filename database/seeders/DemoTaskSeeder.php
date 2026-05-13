<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

// Seeds realistic demo tasks across project board columns for Sara's projects.
class DemoTaskSeeder extends Seeder
{
    public function run(): void
    {
        $assignees = User::whereIn('email', array_merge(
            [DemoUserProfileSeeder::SARA_EMAIL],
            array_column(DemoUserProfileSeeder::TEAMMATES, 'email')
        ))->get()->values();

        foreach (DemoProjectSeeder::PROJECTS as $projectTitle => $meta) {
            $project = Project::where('title', $projectTitle)->firstOrFail();
            $sprint = null;

            if ($project->status === Project::STATUS_ACTIVE) {
                $sprint = Sprint::updateOrCreate(
                    ['project_id' => $project->id, 'name' => 'Sprint 1'],
                    [
                        'goal' => 'Move the core demo workflow toward review-ready quality.',
                        'start_date' => Carbon::parse('2026-05-06')->toDateString(),
                        'end_date' => Carbon::parse($meta['due'] ?? '2026-06-15')->subDays(7)->toDateString(),
                        'status' => 'active',
                        'velocity' => null,
                    ]
                );
            }

            $tasks = $this->tasksFor($projectTitle, $meta['tasks']);
            foreach ($tasks as $index => $taskData) {
                $status = $this->statusFor($index, count($tasks), $project->status);
                $assignee = $assignees[$index % $assignees->count()];
                $due = $meta['due']
                    ? Carbon::parse($meta['due'])->subDays(max(1, count($tasks) - $index))->toDateString()
                    : Carbon::parse('2026-04-01')->addDays($index)->toDateString();

                Task::updateOrCreate(
                    ['project_id' => $project->id, 'title' => $taskData[0]],
                    [
                        'sprint_id' => $status === Task::STATUS_TODO && $index % 3 === 0 ? null : $sprint?->id,
                        'assigned_to' => $assignee->id,
                        'parent_task_id' => null,
                        'role_tag' => fake()->randomElement(['Product Designer', 'Frontend Developer', 'Research Lead', 'PM']),
                        'description' => $taskData[1],
                        'energy' => fake()->randomElement([Task::ENERGY_QUICK_WIN, Task::ENERGY_DEEP_WORK, Task::ENERGY_BLOCKED]),
                        'priority' => fake()->randomElement([Task::PRIORITY_LOW, Task::PRIORITY_MEDIUM, Task::PRIORITY_HIGH]),
                        'story_points' => fake()->randomElement([1, 2, 3, 5, 8]),
                        'status' => $status,
                        'position' => $index,
                        'due_date' => $due,
                        'completed_at' => $status === Task::STATUS_DONE ? Carbon::parse($due)->subDay() : null,
                        'created_at' => Carbon::parse('2026-04-10')->addDays($index % 16),
                        'updated_at' => Carbon::parse('2026-05-08')->subHours($index),
                    ]
                );
            }
        }
    }

    private function statusFor(int $index, int $total, string $projectStatus): string
    {
        if ($projectStatus === Project::STATUS_COMPLETED) {
            return Task::STATUS_DONE;
        }

        $ratio = $index / max(1, $total);
        if ($ratio < 0.35) return Task::STATUS_TODO;
        if ($ratio < 0.72) return Task::STATUS_IN_PROGRESS;
        return Task::STATUS_DONE;
    }

    private function tasksFor(string $projectTitle, int $count): array
    {
        $verbs = [
            'Audit current flow', 'Map user journey', 'Define success metrics', 'Create wireframes',
            'Prototype key interaction', 'Review accessibility states', 'Prepare handoff notes',
            'Run stakeholder review', 'Refine empty states', 'Document component rules',
            'Test mobile breakpoint', 'Write research summary', 'Polish dashboard cards',
            'Align visual hierarchy', 'Create presentation deck', 'Update design tokens',
            'Review copy tone', 'Add analytics annotations', 'Resolve QA feedback',
            'Prepare launch checklist', 'Clean Figma library', 'Write usability script',
            'Synthesize interview notes', 'Design notification states', 'Finalize prototype',
            'Create variant matrix', 'Review edge cases', 'Document design decisions',
            'Pair with engineering', 'Validate data tables', 'Create onboarding checklist',
            'Polish navigation model', 'Review mentor feedback', 'Package final assets',
        ];

        return collect($verbs)
            ->take($count)
            ->map(fn($title) => [
                "{$title} — {$projectTitle}",
                "Demo task for {$projectTitle}: {$title} with clear owner, due date, and acceptance criteria.",
            ])
            ->all();
    }
}
