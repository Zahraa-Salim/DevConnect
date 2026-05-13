<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AliveSignal;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class AliveSignalController extends Controller
{
    public function store(Project $project): JsonResponse
    {
        $isMember = $project->members()
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->exists();

        abort_unless($isMember || auth()->id() === $project->owner_id, 403);

        $recentSignal = AliveSignal::where('project_id', $project->id)
            ->where('user_id', auth()->id())
            ->where('created_at', '>=', now()->subHour())
            ->first();

        if ($recentSignal) {
            return response()->json([
                'signaled'    => false,
                'message'     => 'Already checked in this hour',
                'last_signal' => $recentSignal->created_at?->toISOString(),
            ]);
        }

        $signal = AliveSignal::create([
            'project_id' => $project->id,
            'user_id'    => auth()->id(),
        ]);

        return response()->json([
            'signaled'    => true,
            'signaled_at' => $signal->created_at?->toISOString() ?? now()->toISOString(),
        ]);
    }

    public function index(Project $project): JsonResponse
    {
        $isMember = $project->members()
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->exists();

        abort_unless($isMember || auth()->id() === $project->owner_id, 403);

        $members = $project->members()
            ->where('status', 'active')
            ->with('user:id,name,avatar_url')
            ->get()
            ->map(function ($member) use ($project) {
                $lastSignal = AliveSignal::where('project_id', $project->id)
                    ->where('user_id', $member->user_id)
                    ->latest('created_at')
                    ->first();

                return [
                    'user_id'     => $member->user_id,
                    'name'        => $member->user->name,
                    'avatar_url'  => $member->user->avatar_url,
                    'role'        => $member->role,
                    'last_signal' => $lastSignal?->created_at?->toISOString(),
                    'status'      => $this->getActivityStatus($lastSignal?->created_at),
                ];
            });

        return response()->json($members);
    }

    private function getActivityStatus($lastSignalAt): string
    {
        if (! $lastSignalAt) return 'never';
        $hours = $lastSignalAt->diffInHours(now());
        if ($hours < 24) return 'active';
        if ($hours < 72) return 'away';
        return 'inactive';
    }
}
