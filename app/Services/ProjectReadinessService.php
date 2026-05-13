<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;

class ProjectReadinessService
{
    public function check(Project $project): array
    {
        $checks = [
            [
                'key'   => 'description',
                'label' => 'Project description written',
                'pass'  => ! empty($project->description) && strlen($project->description) > 50,
            ],
            [
                'key'   => 'roles',
                'label' => 'At least one role defined',
                'pass'  => $project->roles()->count() >= 1,
            ],
            [
                'key'   => 'task',
                'label' => 'At least one task added',
                'pass'  => $project->tasks()->count() >= 1,
            ],
            [
                'key'   => 'github',
                'label' => 'GitHub repo linked',
                'pass'  => ! empty($project->repo_url),
            ],
        ];

        return [
            'checks' => $checks,
            'ready'  => collect($checks)->every(fn ($c) => $c['pass']),
        ];
    }
}
