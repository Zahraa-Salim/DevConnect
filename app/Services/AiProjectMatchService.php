<?php

declare(strict_types=1);

namespace App\Services;

use Anthropic\Client;
use App\Models\AiUsageLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AiProjectMatchService
{
    private const MODEL = 'claude-sonnet-4-20250514';

    private Client $client;

    public function __construct()
    {
        $this->client = AnthropicClientFactory::make();
    }

    public function rankProjects(User $user, Collection $projects): array
    {
        $user->loadMissing(['skills', 'workingStyle']);
        $projects->each->loadMissing('roles');

        $userContext = $this->buildUserContext($user);
        $projectsList = $this->buildProjectsList($projects);

        $startTime = hrtime(true);

        try {
            $response = $this->client->messages->create([
                'model' => self::MODEL,
                'max_tokens' => 800,
                'system' => 'You are a project matching assistant. Respond ONLY with valid JSON.',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Given this developer profile: {$userContext}\n\nRank these projects by fit. Return JSON array, best match first:\n[{\"id\": <project_id>, \"score\": <0-100>, \"reason\": \"<one sentence>\"}]\nProjects: {$projectsList}",
                    ],
                ],
            ]);

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
                return $this->fallbackRanking($projects);
            }
        } catch (\Exception $e) {
            $latencyMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            Log::error('AI Project Match failed', [
                'error' => $e->getMessage(),
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

            return $this->fallbackRanking($projects);
        }
    }

    private function buildUserContext(User $user): string
    {
        $skills = $user->skills->pluck('skill_name')->join(', ') ?: 'none listed';

        $ws = $user->workingStyle;
        $styleStr = $ws
            ? "communication={$ws->communication_pref}, feedback={$ws->feedback_style}, weekly_hours={$ws->weekly_hours}"
            : 'not set';

        $parts = [
            "role={$user->role}",
            "skills={$skills}",
            "working_style={$styleStr}",
        ];

        if ($user->github_username) {
            $parts[] = "github={$user->github_username}";
        }

        // github_languages and experience_level are planned columns — guard until migrated
        $githubLanguages = $user->github_languages ?? null;
        if ($githubLanguages) {
            $langs = is_array($githubLanguages) ? implode(', ', $githubLanguages) : $githubLanguages;
            $parts[] = "github_languages={$langs}";
        }

        $experienceLevel = $user->experience_level ?? null;
        if ($experienceLevel) {
            $parts[] = "experience_level={$experienceLevel}";
        }

        return implode('; ', $parts);
    }

    private function buildProjectsList(Collection $projects): string
    {
        return $projects->map(function ($project) {
            $techStack = is_array($project->tech_stack)
                ? implode(', ', $project->tech_stack)
                : ($project->tech_stack ?? 'not specified');

            $openRoles = $project->roles
                ->where('is_open', true)
                ->pluck('role_name')
                ->join(', ') ?: 'none';

            return "id={$project->id}, title=\"{$project->title}\", tech_stack=[{$techStack}], domain={$project->domain}, open_roles=[{$openRoles}]";
        })->join(' | ');
    }

    private function parseResponse(string $content): array
    {
        $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $content = preg_replace('/\s*```$/m', '', $content);
        $content = trim($content);

        $decoded = json_decode($content, associative: true);

        if ($decoded === null || !is_array($decoded)) {
            throw new \RuntimeException('Failed to parse Claude response as JSON array');
        }

        return array_map(fn($item) => [
            'id' => $item['id'],
            'score' => $item['score'],
            'reason' => $item['reason'],
        ], $decoded);
    }

    private function fallbackRanking(Collection $projects): array
    {
        return $projects->map(fn($project) => [
            'id' => $project->id,
            'score' => 0,
            'reason' => '',
        ])->values()->all();
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
                'user_id' => $userId,
                'feature' => AiUsageLog::FEATURE_PROJECT_MATCH,
                'model' => self::MODEL,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'latency_ms' => $latencyMs,
                'status' => $status,
                'error_message' => $errorMessage,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to write AI usage log', ['error' => $e->getMessage()]);
        }
    }
}
