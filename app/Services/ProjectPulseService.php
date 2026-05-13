<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AliveSignal;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Project;
use App\Models\ProjectPulseLog;
use App\Models\Task;

class ProjectPulseService
{
    public function checkHealth(Project $project): array
    {
        $signals = [];

        // Days since last alive signal from any member
        $lastSignal = AliveSignal::where('project_id', $project->id)
            ->latest('created_at')
            ->first();
        $signals['days_since_last_alive'] = $lastSignal
            ? (int) $lastSignal->created_at->diffInDays(now())
            : null;

        // Days since last group chat message
        $groupChat = Conversation::where('project_id', $project->id)
            ->where('type', 'group')
            ->first();
        if ($groupChat) {
            $lastMessage = Message::where('conversation_id', $groupChat->id)
                ->latest('created_at')
                ->first();
            $signals['days_since_last_message'] = $lastMessage
                ? (int) $lastMessage->created_at->diffInDays(now())
                : null;
        } else {
            $signals['days_since_last_message'] = null;
        }

        // Days since last task update
        $lastTask = Task::where('project_id', $project->id)
            ->latest('updated_at')
            ->first();
        $signals['days_since_last_task'] = $lastTask
            ? (int) $lastTask->updated_at->diffInDays(now())
            : null;

        // Active members (with alive signal in last 7 days)
        $signals['active_members'] = AliveSignal::where('project_id', $project->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->distinct('user_id')
            ->count('user_id');

        // Total active members in project
        $signals['total_members'] = $project->members()->where('status', 'active')->count();

        $silenceDays = collect([
            $signals['days_since_last_alive'],
            $signals['days_since_last_message'],
            $signals['days_since_last_task'],
        ])->filter()->min() ?? 999;

        $signals['silence_days'] = $silenceDays;

        if ($silenceDays <= 3) {
            $signals['health'] = 'healthy';
        } elseif ($silenceDays <= 7) {
            $signals['health'] = 'warning';
        } else {
            $signals['health'] = 'critical';
        }

        return $signals;
    }

    public function shouldNudge(Project $project, array $signals): bool
    {
        if ($signals['health'] === 'healthy') {
            return false;
        }

        $recentNudge = ProjectPulseLog::where('project_id', $project->id)
            ->where('status', 'nudge_sent')
            ->where('triggered_at', '>=', now()->subHours(48))
            ->exists();

        return ! $recentNudge && $signals['silence_days'] > 3;
    }

    public function shouldMarkAtRisk(Project $project, array $signals): bool
    {
        if ($signals['health'] !== 'critical') {
            return false;
        }

        $oldNudge = ProjectPulseLog::where('project_id', $project->id)
            ->where('status', 'nudge_sent')
            ->where('triggered_at', '<=', now()->subHours(48))
            ->latest('triggered_at')
            ->first();

        if (! $oldNudge) {
            return false;
        }

        $activityAfterNudge = AliveSignal::where('project_id', $project->id)
            ->where('created_at', '>', $oldNudge->triggered_at)
            ->exists();

        return ! $activityAfterNudge;
    }
}
