<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\ConversationParticipant;
use App\Models\Message;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => fn () => $request->user()
                    ? [
                        'id' => $request->user()->id,
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                        'avatar_url' => $request->user()->avatar_url,
                        'role' => $request->user()->role,
                        'is_verified' => $request->user()->is_verified,
                        'is_admin' => $request->user()->is_admin,
                        'github_username' => $request->user()->github_username,
                    ]
                    : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'notifications' => fn () => [
                'unread_count' => auth()->check() ? auth()->user()->unreadNotifications()->count() : 0,
            ],
            'unreadMessagesCount' => fn () => $request->user()
                ? ConversationParticipant::where('user_id', $request->user()->id)
                    ->get()
                    ->sum(function ($participant) {
                        return Message::where('conversation_id', $participant->conversation_id)
                            ->where('sender_id', '!=', $participant->user_id)
                            ->where('created_at', '>', $participant->last_read_at ?? '1970-01-01')
                            ->whereNull('deleted_at')
                            ->count();
                    })
                : 0,
        ]);
    }
}
