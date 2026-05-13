<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Project;
use App\Models\ProjectPulseLog;
use App\Services\ProjectPulseService;
use Illuminate\Console\Command;

class PulseCheckCommand extends Command
{
    protected $signature = 'pulse:check';

    protected $description = 'Check all active projects for health and send nudges if needed';

    public function handle(ProjectPulseService $service): int
    {
        $projects = Project::whereIn('status', ['active', 'at_risk'])
            ->with('owner')
            ->get();

        $this->info("Checking {$projects->count()} active projects...");

        foreach ($projects as $project) {
            $signals = $service->checkHealth($project);

            $this->line("  [{$project->id}] {$project->title}: health={$signals['health']}, silence={$signals['silence_days']}d");

            if ($service->shouldNudge($project, $signals)) {
                $this->sendNudge($project, $signals);
                $this->warn('    → Nudge sent');
            }

            if ($service->shouldMarkAtRisk($project, $signals)) {
                $this->markAtRisk($project, $signals);
                $this->error('    → Marked AT RISK');
            }

            if ($project->status === 'at_risk' && $signals['health'] === 'healthy') {
                $project->update(['status' => 'active']);
                ProjectPulseLog::create([
                    'project_id' => $project->id,
                    'signals' => $signals,
                    'status' => ProjectPulseLog::STATUS_RESOLVED,
                    'triggered_at' => now(),
                ]);
                $this->info('    → Resolved: back to active');
            }
        }

        $this->info('Pulse check complete.');

        return Command::SUCCESS;
    }

    private function sendNudge(Project $project, array $signals): void
    {
        ProjectPulseLog::create([
            'project_id' => $project->id,
            'signals' => $signals,
            'status' => ProjectPulseLog::STATUS_NUDGE_SENT,
            'triggered_at' => now(),
        ]);

        $groupChat = Conversation::where('project_id', $project->id)
            ->where('type', 'group')
            ->first();

        if ($groupChat) {
            Message::create([
                'conversation_id' => $groupChat->id,
                'sender_id' => $project->owner_id,
                'body' => "🤖 Project Pulse: Hey team, it's been {$signals['silence_days']} days since the last activity on \"{$project->title}\". How's everything going? Drop a message or tap 'I'm Alive' to let everyone know you're still on track!",
            ]);
            $groupChat->update(['last_message_at' => now()]);
        }
    }

    private function markAtRisk(Project $project, array $signals): void
    {
        $project->update(['status' => 'at_risk']);

        ProjectPulseLog::create([
            'project_id' => $project->id,
            'signals' => $signals,
            'status' => ProjectPulseLog::STATUS_AT_RISK,
            'triggered_at' => now(),
        ]);

        $groupChat = Conversation::where('project_id', $project->id)
            ->where('type', 'group')
            ->first();

        if ($groupChat) {
            Message::create([
                'conversation_id' => $groupChat->id,
                'sender_id' => $project->owner_id,
                'body' => "⚠️ Project Pulse: This project has been marked as At Risk due to prolonged inactivity ({$signals['silence_days']} days). Team members — please check in or update the project status. The owner can resolve this in Settings.",
            ]);
            $groupChat->update(['last_message_at' => now()]);
        }
    }
}
