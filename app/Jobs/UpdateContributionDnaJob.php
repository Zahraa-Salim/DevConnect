<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ContributionLog;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateContributionDnaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function handle(): void
    {
        $tasksAssigned = Task::where('assigned_to', $this->user->id)->count();
        $tasksDone     = Task::where('assigned_to', $this->user->id)->where('status', Task::STATUS_DONE)->count();
        $mergedCount   = ContributionLog::where('user_id', $this->user->id)
            ->where('status', ContributionLog::STATUS_MERGED)
            ->count();
        $completedProjects = ProjectMember::where('user_id', $this->user->id)
            ->whereHas('project', fn($q) => $q->where('status', 'completed'))
            ->count();

        $label = match (true) {
            $mergedCount >= 3                                         => 'Contributor',
            ($tasksDone / max($tasksAssigned, 1)) >= 0.8             => 'Closer',
            $tasksDone >= 10                                          => 'Builder',
            $completedProjects >= 2                                   => 'Finisher',
            default                                                   => 'Explorer',
        };

        $this->user->contribution_dna = [
            'label'              => $label,
            'tasks_done'         => $tasksDone,
            'prs_merged'         => $mergedCount,
            'projects_completed' => $completedProjects,
            'updated_at'         => now()->toDateString(),
        ];

        $this->user->save();
    }
}
