<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Messages/Index', [
            'conversations' => $this->getConversations($request),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_id' => ['required', 'exists:users,id'],
        ]);

        $recipientId = (int) $validated['recipient_id'];

        if ($recipientId === $request->user()->id) {
            return back()->withErrors(['message' => 'You cannot message yourself.']);
        }

        $existingDm = Conversation::where('type', Conversation::TYPE_DM)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $request->user()->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $recipientId))
            ->first();

        if ($existingDm) {
            return redirect()->route('messages.show', $existingDm->id);
        }

        $dm = Conversation::create([
            'type' => Conversation::TYPE_DM,
            'thread_type' => Conversation::THREAD_MAIN,
            'last_message_at' => now(),
        ]);

        $dm->participants()->attach([
            $request->user()->id,
            $recipientId,
        ]);

        return redirect()->route('messages.show', $dm->id);
    }

    public function show(Request $request, Conversation $conversation)
    {
        abort_if(
            !ConversationParticipant::where('conversation_id', $conversation->id)
                ->where('user_id', $request->user()->id)
                ->exists(),
            403
        );

        $messages = $conversation->messages()
            ->with('sender:id,name,avatar_url')
            ->orderBy('created_at', 'asc')
            ->limit(50)
            ->get();

        $participant = ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $request->user()->id)
            ->first();

        $participant->update(['last_read_at' => now()]);

        $conversation->load([
            'participants:id,name,avatar_url',
            'project:id,title',
        ]);

        $otherParticipant = $conversation->type === Conversation::TYPE_DM
            ? $conversation->participants->firstWhere('id', '!=', $request->user()->id)
            : null;

        return Inertia::render('Messages/Index', [
            'conversations' => $this->getConversations($request),
            'activeConversation' => [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'thread_type' => $conversation->thread_type,
                'name' => $conversation->type === Conversation::TYPE_DM
                    ? $otherParticipant?->name
                    : $conversation->project?->title,
                'otherParticipant' => $otherParticipant,
            ],
            'messages' => $messages->map(fn ($msg) => [
                'id' => $msg->id,
                'conversation_id' => $msg->conversation_id,
                'sender' => [
                    'id' => $msg->sender->id,
                    'name' => $msg->sender->name,
                    'avatar_url' => $msg->sender->avatar_url,
                ],
                'body' => $msg->body,
                'edited_at' => $msg->edited_at,
                'deleted_at' => $msg->deleted_at,
                'created_at' => $msg->created_at->toIso8601String(),
            ]),
        ]);
    }

    private function getConversations(Request $request): Collection
    {
        $userId = $request->user()->id;

        return Conversation::whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->with([
                'participants' => fn ($q) => $q->select('users.id', 'users.name', 'users.avatar_url'),
                'messages' => fn ($q) => $q->latest()->take(1)->with('sender:id,name,avatar_url'),
                'project:id,title',
            ])
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->input('search'), fn ($q, $search) => $q->whereHas('participants', fn ($subQ) => $subQ->where('name', 'LIKE', "%{$search}%")))
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function ($conversation) use ($userId) {
                $otherParticipant = $conversation->participants->firstWhere('id', '!=', $userId);
                $participant = ConversationParticipant::where('conversation_id', $conversation->id)
                    ->where('user_id', $userId)
                    ->first();

                $unreadCount = Message::where('conversation_id', $conversation->id)
                    ->where('sender_id', '!=', $userId)
                    ->where('created_at', '>', $participant?->last_read_at ?? '1970-01-01')
                    ->whereNull('deleted_at')
                    ->count();

                $lastMessage = $conversation->messages->first();

                if ($conversation->type === Conversation::TYPE_DM) {
                    $displayName = $otherParticipant?->name ?? 'Unknown';
                    $displayAvatar = $otherParticipant?->avatar_url;
                    $displayInitials = substr($otherParticipant?->name ?? '', 0, 1) . substr(explode(' ', $otherParticipant?->name ?? '')[1] ?? '', 0, 1);
                } else {
                    $displayName = $conversation->project?->title ?? 'Group Chat';
                    $displayAvatar = null;
                    $displayInitials = substr($conversation->project?->title ?? '', 0, 2);
                }

                if ($lastMessage) {
                    if ($conversation->type === Conversation::TYPE_GROUP) {
                        $preview = ($lastMessage->sender_id === $userId ? 'You: ' : $lastMessage->sender->name . ': ') . $lastMessage->body;
                    } else {
                        $preview = ($lastMessage->sender_id === $userId ? 'You: ' : '') . $lastMessage->body;
                    }
                } else {
                    $preview = '';
                }

                return [
                    'id' => $conversation->id,
                    'type' => $conversation->type,
                    'thread_type' => $conversation->thread_type,
                    'project_id' => $conversation->project_id,
                    'display_name' => $displayName,
                    'display_avatar' => $displayAvatar,
                    'display_initials' => $displayInitials,
                    'name' => $displayName,
                    'initials' => $displayInitials,
                    'preview' => $preview,
                    'timestamp' => $lastMessage
                        ? $this->formatTime($lastMessage->created_at)
                        : '',
                    'unread' => $unreadCount > 0,
                    'unread_count' => $unreadCount,
                    'last_message_at' => $conversation->last_message_at,
                ];
            });
    }

    private function formatTime($dateTime): string
    {
        $now = now();
        $date = $dateTime->toDateTimeImmutable();

        if ($date->format('Y-m-d') === $now->format('Y-m-d')) {
            return $date->format('h:i A');
        }

        if ($date->format('Y-m-d') === $now->subDay()->format('Y-m-d')) {
            return 'Yesterday';
        }

        return $date->format('M d');
    }
}
