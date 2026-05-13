<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ContributionLog;
use App\Models\GithubIssue;
use App\Services\AiIssueRankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ContributeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = GithubIssue::where('state', GithubIssue::STATE_OPEN)
            ->orderBy('fetched_at', 'desc');

        if ($language = $request->query('language')) {
            $query->where('language', $language);
        }

        $issues = $query->limit(50)->get();

        $languages = GithubIssue::where('state', GithubIssue::STATE_OPEN)
            ->distinct()
            ->orderBy('language')
            ->pluck('language')
            ->filter()
            ->values();

        $userLogs = collect();
        if (auth()->check()) {
            $userLogs = ContributionLog::where('user_id', auth()->id())
                ->whereIn('github_issue_id', $issues->pluck('id'))
                ->get()
                ->keyBy('github_issue_id');
        }

        $aiRanked = $request->boolean('ai_ranked') && auth()->check();

        if ($aiRanked) {
            try {
                $rankings = (new AiIssueRankingService())->rankIssues(auth()->user(), $issues);

                if (! empty($rankings)) {
                    $scoreMap = collect($rankings)->keyBy('id');

                    $issues = $issues
                        ->map(function ($issue) use ($scoreMap) {
                            $match = $scoreMap->get($issue->id);
                            $issue->setAttribute('ai_score', $match['score'] ?? 0);
                            $issue->setAttribute('ai_reason', $match['reason'] ?? '');
                            return $issue;
                        })
                        ->sortByDesc('ai_score')
                        ->values();
                }
            } catch (\Throwable $e) {
                Log::warning('AI Issue Ranking unavailable', ['error' => $e->getMessage()]);
            }
        }

        return Inertia::render('Contribute/Index', [
            'issues'    => $issues,
            'languages' => $languages,
            'userLogs'  => $userLogs,
            'aiRanked'  => $aiRanked,
            'filters'   => $request->only(['language']),
        ]);
    }

    public function aiRank(): JsonResponse
    {
        $user   = auth()->user();
        $issues = GithubIssue::where('state', GithubIssue::STATE_OPEN)
            ->orderBy('fetched_at', 'desc')
            ->limit(50)
            ->get();

        try {
            $ranked = (new AiIssueRankingService())->rankIssues($user, $issues);
            return response()->json(['ranked' => $ranked]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'AI ranking unavailable'], 503);
        }
    }
}
