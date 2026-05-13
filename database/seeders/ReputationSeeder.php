<?php

namespace Database\Seeders;

use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReputationSeeder extends Seeder
{
    public function run(): void
    {
        // Get all users that have received ratings
        $ratedUserIds = Rating::query()
            ->whereNotNull('rated_id')
            ->distinct()
            ->pluck('rated_id');

        foreach ($ratedUserIds as $userId) {
            $ratings = Rating::where('rated_id', $userId)->get();
            $count   = $ratings->count();

            if ($count === 0) {
                continue;
            }

            // score = avg(communication + reliability + contribution) / 3 * 20
            $avgSum = $ratings->avg(fn ($r) => $r->communication_score + $r->reliability_score + $r->contribution_score);
            $score  = round($avgSum / 3 * 20, 2);

            $isVerified = $count >= 2 && $score >= 60;

            User::where('id', $userId)->update([
                'reputation_score' => $score,
                'is_verified'      => $isVerified,
            ]);
        }
    }
}
