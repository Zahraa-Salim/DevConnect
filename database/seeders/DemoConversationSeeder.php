<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Seeds demo group chats, DM conversations, participants, and realistic messages.
class DemoConversationSeeder extends Seeder
{
    public function run(): void
    {
        $sara = User::where('email', DemoUserProfileSeeder::SARA_EMAIL)->firstOrFail();
        $ahmad = User::where('email', 'ahmad.nasser@demo.io')->firstOrFail();
        $allDemoUsers = User::where('email', 'like', 'demo.member%')
            ->orWhereIn('email', array_column(DemoUserProfileSeeder::TEAMMATES, 'email'))
            ->orderBy('id')
            ->get()
            ->prepend($sara)
            ->unique('id')
            ->values();

        $this->seedGroup(
            Project::where('title', 'Redesign Fintech Dashboard')->firstOrFail(),
            $allDemoUsers->take(14),
            340,
            Carbon::parse('2026-05-11 10:00:00'),
            ['dashboard', 'fintech', 'handoff']
        );

        $this->seedGroup(
            null,
            $allDemoUsers->take(8),
            180,
            Carbon::parse('2026-05-10 12:00:00'),
            ['portfolio', 'career', 'mentorship'],
            Conversation::THREAD_ADVISOR
        );

        $this->seedGroup(
            Project::where('title', 'Design System v2.0')->firstOrFail(),
            $allDemoUsers->take(7),
            210,
            Carbon::parse('2026-05-08 12:00:00'),
            ['tokens', 'components', 'accessibility']
        );

        $this->seedGroup(
            null,
            $allDemoUsers->take(126),
            40,
            Carbon::parse('2026-05-11 09:00:00'),
            ['community', 'design', 'weekly challenge'],
            Conversation::THREAD_MAIN
        );

        $this->seedDm($sara, $ahmad, 62, Carbon::parse('2026-05-11 11:00:00'));
    }

    private function seedGroup(?Project $project, $participants, int $messageCount, Carbon $lastAt, array $topics, string $threadType = Conversation::THREAD_MAIN): void
    {
        $conversation = Conversation::firstOrCreate(
            [
                'type' => Conversation::TYPE_GROUP,
                'thread_type' => $threadType,
                'project_id' => $project?->id,
            ],
            ['last_message_at' => $lastAt]
        );

        $conversation->update(['last_message_at' => $lastAt]);
        $this->syncParticipants($conversation, $participants, $lastAt->copy()->subDays(2));
        $this->seedMessages($conversation, $participants->values(), $messageCount, $lastAt, $topics);
    }

    private function seedDm(User $sara, User $ahmad, int $messageCount, Carbon $lastAt): void
    {
        $conversation = Conversation::where('type', Conversation::TYPE_DM)
            ->whereHas('participants', fn($q) => $q->where('user_id', $sara->id))
            ->whereHas('participants', fn($q) => $q->where('user_id', $ahmad->id))
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create([
                'type' => Conversation::TYPE_DM,
                'thread_type' => Conversation::THREAD_MAIN,
                'last_message_at' => $lastAt,
            ]);
        }

        $conversation->update(['last_message_at' => $lastAt]);
        $participants = collect([$sara, $ahmad]);
        $this->syncParticipants($conversation, $participants, $lastAt->copy()->subHours(8));
        $this->seedMessages($conversation, $participants, $messageCount, $lastAt, ['portfolio', 'project feedback', 'pricing']);
    }

    private function syncParticipants(Conversation $conversation, $participants, Carbon $lastReadAt): void
    {
        foreach ($participants as $participant) {
            ConversationParticipant::updateOrCreate(
                ['conversation_id' => $conversation->id, 'user_id' => $participant->id],
                ['last_read_at' => $lastReadAt]
            );
        }
    }

    private function seedMessages(Conversation $conversation, $participants, int $count, Carbon $lastAt, array $topics): void
    {
        $samples = [
            'I updated the latest Figma frame and left notes for review.',
            'This direction feels clearer for new users.',
            'Can we validate this with one more user before handoff?',
            'I will clean up the edge cases this afternoon.',
            'The hierarchy is much better after the last pass.',
            'Let us keep the scope tight and document the tradeoffs.',
            'I added a quick comment on the component behavior.',
            'This should be ready for stakeholder review soon.',
            'Can someone check the empty state copy?',
            'Nice, this makes the flow easier to explain.',
        ];

        $start = $lastAt->copy()->subDays(30);
        $stepMinutes = max(1, (int) floor($start->diffInMinutes($lastAt) / max(1, $count - 1)));
        $existing = DB::table('messages')
            ->where('conversation_id', $conversation->id)
            ->select('sender_id', 'body', 'created_at')
            ->get()
            ->mapWithKeys(fn($message) => [
                $message->sender_id . '|' . $message->body . '|' . Carbon::parse($message->created_at)->toDateTimeString() => true,
            ]);

        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $sender = $participants[$i % $participants->count()];
            $createdAt = $start->copy()->addMinutes($i * $stepMinutes);
            if ($i === $count - 1) {
                $createdAt = $lastAt->copy();
            }

            $body = $samples[$i % count($samples)] . ' #' . $topics[$i % count($topics)];
            $key = $sender->id . '|' . $body . '|' . $createdAt->toDateTimeString();

            if ($existing->has($key)) {
                continue;
            }

            $rows[] = [
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'body' => $body,
                'attachment_url' => null,
                'attachment_type' => null,
                'edited_at' => null,
                'deleted_at' => null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if (count($rows) >= 200) {
                DB::table('messages')->insert($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            DB::table('messages')->insert($rows);
        }
    }
}
