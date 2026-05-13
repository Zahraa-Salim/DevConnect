<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse|Response
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => $this->formatNotification($notification))
            ->values();

        if (! $request->wantsJson()) {
            return Inertia::render('Notifications/Index', [
                'notifications' => $notifications,
            ]);
        }

        return response()->json($notifications);
    }

    private function formatNotification(DatabaseNotification $notification): array
    {
        $data = $this->normalizeData($notification->data);

        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'data' => [
                'title' => $data['title'] ?? 'Notification',
                'message' => $data['message'] ?? 'You have a new update.',
                'url' => $data['url'] ?? '/dashboard',
                'type' => $data['type'] ?? null,
            ],
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at,
        ];
    }

    private function normalizeData(mixed $data): array
    {
        if (is_array($data)) {
            return $data;
        }

        if (is_string($data)) {
            $decoded = json_decode($data, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    public function markRead(string $id): JsonResponse
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markUnread(string $id): JsonResponse
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->forceFill(['read_at' => null])->save();

        return response()->json(['success' => true]);
    }

    public function markAllRead(): JsonResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }
}
