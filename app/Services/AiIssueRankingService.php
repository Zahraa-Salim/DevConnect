<?php

declare(strict_types=1);

namespace App\Services;

use Anthropic\Client;
use App\Models\AiUsageLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AiIssueRankingService
{
    private const MODEL = 'claude-sonnet-4-20250514';

    private Client $client;

    public function __construct()
    {
        $this->client = AnthropicClientFactory::make();
    }

    public function rankIssues(User $user, Collection $issues): array
    {
        $user->loadMissing('skills');

        return Cache::remember("issue_rank_{$user->id}", 21600, function () use ($user, $issues) {
            $userContext = $this->buildUserContext($user);
            $issuesList  = $this->buildIssuesList($issues);

            $startTime = hrtime(true);

            try {
                $response = $this->client->messages->create(
                    maxTokens: 600,
                    messages: [
                        [
                            'role'    => 'user',
                            'content' => "Given this developer: {$userContext}\n\nRank these GitHub issues by fit. Return JSON array best first:\n[{\"id\": <id>, \"score\": <0-100>, \"reason\": \"<one sentence>\"}]\nIssues: {$issuesList}",
                        ],
                    ],
                    model: self::MODEL,
                    system: 'You are a contribution matching assistant. Respond ONLY with valid JSON.',
                );

                $latencyMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

                $this->logUsage(
                    userId: $user->id,
                    promptTokens: $response->usage->input_tokens,
                    completionTokens: $response->usage->output_tokens,
                    latencyMs: $latencyMs,
                    status: AiUsageLog::STATUS_SUCCESS,
                );

                try {
                    return $this->parseResponse($response->content[0]->text);
                } catch (\RuntimeException) {
                    return [];
                }
            } catch (\Throwable $e) {
                $latencyMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

                Log::error('AI Issue Ranking failed', [
                    'error'   => $e->getMessage(),
                    'user_id' => $user->id,
                ]);

                $this->logUsage(
                    userId: $user->id,
                    promptTokens: 0,
                    completionTokens: 0,
                    latencyMs: $latencyMs,
                    status: AiUsageLog::STATUS_ERROR,
                    errorMessage: $e->getMessage(),
                );

                return [];
            }
        });
    }

    private function buildUserContext(User $user): string
    {
        $skills = $user->skills->pluck('skill_name')->join(', ') ?: 'none listed';

        $parts = [
            "role={$user->role}",
            "skills={$skills}",
        ];

        $githubLanguages = $user->github_languages ?? null;
        if ($githubLanguages) {
            $langs   = is_array($githubLanguages) ? implode(', ', $githubLanguages) : $githubLanguages;
            $parts[] = "github_languages={$langs}";
        }

        $experienceLevel = $user->experience_level ?? null;
        if ($experienceLevel) {
            $parts[] = "experience_level={$experienceLevel}";
        }

        return implode('; ', $parts);
    }

    private function buildIssuesList(Collection $issues): string
    {
        return $issues->map(function ($issue) {
            $labels = is_array($issue->labels) ? implode(', ', $issue->labels) : '';
            return "id={$issue->id}, title=\"{$issue->title}\", repo={$issue->repo_full_name}, language={$issue->language}, difficulty={$issue->difficulty}, labels=[{$labels}]";
        })->join(' | ');
    }

    private function parseResponse(string $content): array
    {
        $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $content = preg_replace('/\s*```$/m', '', $content);
        $content = trim($content);

        $decoded = json_decode($content, associative: true);

        if ($decoded === null || ! is_array($decoded)) {
            throw new \RuntimeException('Failed to parse Claude response as JSON array');
        }

        return array_map(fn($item) => [
            'id'     => $item['id'],
            'score'  => $item['score'],
            'reason' => $item['reason'],
        ], $decoded);
    }

    private function logUsage(
        int $userId,
        int $promptTokens,
        int $completionTokens,
        int $latencyMs,
        string $status,
        ?string $errorMessage = null,
    ): void {
        try {
            AiUsageLog::create([
                'user_id'           => $userId,
                'feature'           => AiUsageLog::FEATURE_ISSUE_MATCH,
                'model'             => self::MODEL,
                'prompt_tokens'     => $promptTokens,
                'completion_tokens' => $completionTokens,
                'latency_ms'        => $latencyMs,
                'status'            => $status,
                'error_message'     => $errorMessage,
                'created_at'        => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to write AI usage log', ['error' => $e->getMessage()]);
        }
    }
}
