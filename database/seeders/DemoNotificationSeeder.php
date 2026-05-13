<?php

namespace Database\Seeders;

use App\Models\HelpRequest;
use App\Models\MentorSlot;
use App\Models\ProjectIdea;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Database\Seeder;

// Seeds unread demo notifications for Sara across tasks, ideas, sessions, and mentee requests.
class DemoNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $sara = User::where('email', DemoUserProfileSeeder::SARA_EMAIL)->firstOrFail();
        $tasks = Task::where('assigned_to', $sara->id)->orderBy('due_date')->limit(3)->get();
        $ideas = ProjectIdea::where('submitted_by', $sara->id)->orderBy('id')->limit(2)->get();
        $slots = MentorSlot::whereHas('booking')
            ->where('mentor_id', $sara->id)
            ->where('starts_at', '>', Carbon::parse('2026-05-11 00:00:00'))
            ->orderBy('starts_at')
            ->limit(2)
            ->get();
        $helpRequest = HelpRequest::where('mentor_id', $sara->id)->latest('id')->first();

        $items = [];

        foreach ($tasks as $task) {
            $items[] = [
                'key' => 'task-' . $task->id,
                'title' => 'New task assigned',
                'message' => $task->title,
                'url' => '/projects/' . $task->project_id,
                'source_type' => Task::class,
                'source_id' => $task->id,
            ];
        }

        foreach ($ideas as $idea) {
            $items[] = [
                'key' => 'idea-' . $idea->id,
                'title' => 'New comment on your idea',
                'message' => 'Someone added feedback on "' . $idea->title . '".',
                'url' => '/ideas/' . $idea->id,
                'source_type' => ProjectIdea::class,
                'source_id' => $idea->id,
            ];
        }

        foreach ($slots as $slot) {
            $items[] = [
                'key' => 'slot-' . $slot->id,
                'title' => 'Upcoming mentor session',
                'message' => 'You have a confirmed session on ' . $slot->starts_at->format('M j, H:i') . '.',
                'url' => '/mentor/dashboard',
                'source_type' => MentorSlot::class,
                'source_id' => $slot->id,
            ];
        }

        if ($helpRequest) {
            $items[] = [
                'key' => 'help-' . $helpRequest->id,
                'title' => 'New community question',
                'message' => $helpRequest->title ?? 'Someone posted a question.',
                'url' => '/mentor/dashboard',
                'source_type' => HelpRequest::class,
                'source_id' => $helpRequest->id,
            ];
        }

        foreach (array_slice($items, 0, 8) as $index => $item) {
            $createdAt = Carbon::parse('2026-05-10 09:00:00')->addHours($index * 3);

            DatabaseNotification::firstOrCreate(
                [
                    'notifiable_type' => User::class,
                    'notifiable_id' => $sara->id,
                    'type' => 'demo.notification',
                    'source_type' => $item['source_type'],
                    'source_id' => $item['source_id'],
                ],
                [
                    'id' => $this->stableUuid('sara-demo-' . $item['key']),
                    'data' => [
                        'title' => $item['title'],
                        'message' => $item['message'],
                        'url' => $item['url'],
                    ],
                    'read_at' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );
        }
    }

    private function stableUuid(string $seed): string
    {
        $hash = md5($seed);

        return substr($hash, 0, 8) . '-' .
            substr($hash, 8, 4) . '-' .
            substr($hash, 12, 4) . '-' .
            substr($hash, 16, 4) . '-' .
            substr($hash, 20, 12);
    }
}
