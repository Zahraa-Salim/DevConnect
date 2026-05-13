<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ConversationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_open_conversation(): void
    {
        $user = User::factory()->create(['name' => 'Sara']);
        $otherUser = User::factory()->create(['name' => 'Rami']);
        $conversation = Conversation::create([
            'type' => Conversation::TYPE_DM,
            'thread_type' => Conversation::THREAD_MAIN,
            'last_message_at' => now(),
        ]);

        $conversation->participants()->attach([
            $user->id => ['last_read_at' => null],
            $otherUser->id => ['last_read_at' => null],
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $otherUser->id,
            'body' => 'Hello from the other side.',
        ]);

        $this->actingAs($user)
            ->get(route('messages.show', $conversation->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Messages/Index')
                ->has('conversations', 1)
                ->where('activeConversation.id', $conversation->id)
                ->where('activeConversation.name', 'Rami')
                ->has('messages', 1)
                ->where('messages.0.body', 'Hello from the other side.')
            );
    }
}
