<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Notifications\ReputationUpdatedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateUserReputationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public User $user) {}

    public function handle(): void
    {
        $ratings = $this->user->ratingsReceived()->get();

        if ($ratings->isEmpty()) {
            $this->user->reputation_score = 0;
            $this->user->is_verified = false;
            $this->user->save();
            return;
        }

        $perRatingAverages = $ratings->map(
            fn($r) => ($r->communication_score + $r->reliability_score + $r->contribution_score) / 3
        );

        $score = round($perRatingAverages->average() * 20, 1);

        $wasVerified = (bool) $this->user->is_verified;
        $nowVerified = $ratings->count() >= 2 && $score >= 60;

        $this->user->reputation_score = $score;
        $this->user->is_verified = $nowVerified;
        $this->user->save();

        if (! $wasVerified && $nowVerified) {
            $this->user->notify(new ReputationUpdatedNotification());
        }

        Log::info("Reputation calculated for user {$this->user->id}: score={$score}, verified=" . ($nowVerified ? 'true' : 'false'));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("CalculateUserReputationJob failed for user {$this->user->id}: {$exception->getMessage()}");
    }
}
