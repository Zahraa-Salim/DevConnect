<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\NewMessage;
use App\Events\MessageUpdated;
use App\Events\MessageDeleted;
use App\Events\UserTyping;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public function store(Request $request, Conversation $conversation)
    {
        abort_if(
            !ConversationParticipant::where('conversation_id', $conversation->id)
                ->where('user_id', $request->user()->id)
                ->exists(),
            403
        );

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        $message->load('sender:id,name,avatar_url');

        $this->broadcastToOthers(new NewMessage($message));

        return response()->json([
            'message' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender' => [
                    'id' => $message->sender->id,
                    'name' => $message->sender->name,
                    'avatar_url' => $message->sender->avatar_url,
                ],
                'body' => $message->body,
                'edited_at' => $message->edited_at,
                'deleted_at' => $message->deleted_at,
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request, Message $message)
    {
        abort_if($request->user()->id !== $message->sender_id, 403);
        abort_if($message->deleted_at !== null, 400);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message->update([
            'body' => $validated['body'],
            'edited_at' => now(),
        ]);

        $message->load('sender:id,name,avatar_url');

        $this->broadcastToOthers(new MessageUpdated($message));

        return response()->json([
            'message' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender' => [
                    'id' => $message->sender->id,
                    'name' => $message->sender->name,
                    'avatar_url' => $message->sender->avatar_url,
                ],
                'body' => $message->body,
                'edited_at' => $message->edited_at,
                'deleted_at' => $message->deleted_at,
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ]);
    }

    public function destroy(Message $message, Request $request)
    {
        abort_if($request->user()->id !== $message->sender_id, 403);

        $conversationId = $message->conversation_id;
        $messageId = $message->id;

        $message->delete();

        $this->broadcastToOthers(new MessageDeleted($conversationId, $messageId));

        return response()->noContent();
    }

    public function typing(Request $request, Conversation $conversation)
    {
        abort_if(
            !ConversationParticipant::where('conversation_id', $conversation->id)
                ->where('user_id', $request->user()->id)
                ->exists(),
            403
        );

        $this->broadcast(new UserTyping(
            $conversation->id,
            [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
            ]
        ));

        return response()->noContent();
    }

    private function broadcast(object $event): void
    {
        if (! config('broadcasting.enabled')) {
            return;
        }

        try {
            broadcast($event);
        } catch (BroadcastException $exception) {
            Log::warning('Broadcast failed.', [
                'event' => $event::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function broadcastToOthers(object $event): void
    {
        if (! config('broadcasting.enabled')) {
            return;
        }

        try {
            broadcast($event)->toOthers();
        } catch (BroadcastException $exception) {
            Log::warning('Broadcast failed.', [
                'event' => $event::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
