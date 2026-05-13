<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Analytics', [
            'projects_per_week'       => $this->projectsPerWeek(),
            'ai_usage'                => $this->aiUsage(),
            'reputation_distribution' => $this->reputationDistribution(),
        ]);
    }

    private function projectsPerWeek(): array
    {
        $rows = DB::table('projects')
            ->selectRaw("strftime('%Y-%W', created_at) as week, COUNT(*) as count, MIN(created_at) as week_start")
            ->where('created_at', '>=', now()->subWeeks(8))
            ->groupByRaw("strftime('%Y-%W', created_at)")
            ->orderBy('week')
            ->get();

        return $rows->map(fn ($r) => [
            'label' => 'Week of ' . date('M j', strtotime($r->week_start)),
            'count' => $r->count,
        ])->values()->all();
    }

    private function aiUsage(): array
    {
        return AiUsageLog::selectRaw('feature, COUNT(*) as calls, SUM(prompt_tokens + completion_tokens) as tokens')
            ->groupBy('feature')
            ->orderByDesc('calls')
            ->get()
            ->map(fn ($r) => [
                'feature' => $r->feature,
                'calls'   => (int) $r->calls,
                'tokens'  => (int) $r->tokens,
            ])
            ->values()
            ->all();
    }

    private function reputationDistribution(): array
    {
        $scores = User::pluck('reputation_score')->map(fn ($s) => (float) $s);

        $ranges = [
            '0–20'   => 0,
            '21–40'  => 0,
            '41–60'  => 0,
            '61–80'  => 0,
            '81–100' => 0,
        ];

        foreach ($scores as $score) {
            if ($score <= 20)      $ranges['0–20']++;
            elseif ($score <= 40)  $ranges['21–40']++;
            elseif ($score <= 60)  $ranges['41–60']++;
            elseif ($score <= 80)  $ranges['61–80']++;
            else                   $ranges['81–100']++;
        }

        return collect($ranges)->map(fn ($count, $label) => [
            'label' => $label,
            'count' => $count,
        ])->values()->all();
    }
}
