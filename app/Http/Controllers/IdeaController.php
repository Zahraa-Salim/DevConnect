<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\IdeaVote;
use App\Models\ProjectIdea;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IdeaController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ProjectIdea::query()
            ->with(['submitter:id,name,avatar_url'])
            ->whereIn('status', ['active', 'featured']);

        if ($source = $request->query('source')) {
            $query->where('source', $source);
        }
        if ($difficulty = $request->query('difficulty')) {
            $query->where('difficulty', $difficulty);
        }
        if ($domain = $request->query('domain')) {
            $query->where('domain', $domain);
        }

        $ideas = $query->latest()->paginate(12)->withQueryString();

        $userVotedIds = auth()->check()
            ? IdeaVote::where('user_id', auth()->id())
                ->whereIn('idea_id', $ideas->pluck('id'))
                ->pluck('idea_id')
                ->toArray()
            : [];

        return Inertia::render('Ideas', [
            'ideas' => $ideas,
            'filters' => $request->only(['source', 'difficulty', 'domain']),
            'userVotedIds' => $userVotedIds,
        ]);
    }

    public function show(ProjectIdea $idea): Response
    {
        if (! in_array($idea->status, ['active', 'featured', 'converted'])) {
            abort(404);
        }

        $idea->load([
            'submitter:id,name,avatar_url',
            'comments' => fn ($q) => $q->with('user:id,name,avatar_url')->latest(),
        ]);

        $hasVoted = auth()->check()
            ? IdeaVote::where('idea_id', $idea->id)
                ->where('user_id', auth()->id())
                ->exists()
            : false;

        // Load AI usage data for AI-generated ideas
        $aiUsage = null;
        if ($idea->source === 'ai') {
            $usage = \App\Models\AiUsageLog::where('user_id', $idea->submitted_by)
                ->where('feature', 'idea_gen')
                ->latest()
                ->first();

            if ($usage) {
                $aiUsage = [
                    'tokens' => $usage->prompt_tokens + $usage->completion_tokens,
                    'cost' => $this->estimateTokenCost($usage->prompt_tokens, $usage->completion_tokens),
                    'model' => $usage->model,
                ];
            }
        }

        return Inertia::render('Ideas/Show', [
            'idea' => $idea,
            'hasVoted' => $hasVoted,
            'aiUsage' => $aiUsage,
        ]);
    }

    private function estimateTokenCost(int $promptTokens, int $completionTokens): string
    {
        // Claude Sonnet 4 pricing (as of May 2025):
        // Input: $3.00 per 1M tokens
        // Output: $15.00 per 1M tokens
        $inputCost = ($promptTokens / 1_000_000) * 3.00;
        $outputCost = ($completionTokens / 1_000_000) * 15.00;
        $totalCost = $inputCost + $outputCost;

        return number_format($totalCost, 4);
    }
}
