<?php

declare(strict_types=1);

namespace App\Services;

use Anthropic\Client;
use App\Models\AiSuggestion;
use App\Models\AiUsageLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AiProfileSuggestionService
{
    private const MODEL = 'claude-sonnet-4-20250514';

    private Client $client;

    public function __construct()
    {
        $this->client = AnthropicClientFactory::make();
    }

    public function generate(User $user, Project $project): array
    {
        $context   = $this->buildProjectContext($user, $project);
        $prompt    = $this->buildPrompt($context);
        $startTime = hrtime(true);

        try {
            $response = $this->client->messages->create([
                'model'      => self::MODEL,
                'max_tokens' => 1000,
                'system'     => 'You are a professional career writing assistant. Respond ONLY with valid JSON.',
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            $latencyMs   = (int) ((hrtime(true) - $startTime) / 1_000_000);
            $inputTokens  = $response->usage->input_tokens;
            $outputTokens = $response->usage->output_tokens;
            $tokensUsed   = $inputTokens + $outputTokens;

            $this->logUsage(
                userId: $user->id,
                promptTokens: $inputTokens,
                completionTokens: $outputTokens,
                latencyMs: $latencyMs,
                status: AiUsageLog::STATUS_SUCCESS,
            );

            try {
                $parsed = $this->parseResponse($response->content[0]->text);
            } catch (\RuntimeException) {
                return ['cv_bullet' => '', 'portfolio_description' => '', 'linkedin_post' => '', 'error' => true];
            }

            $this->upsertSuggestion($user, $project, $parsed, $tokensUsed);

            return [
                'cv_bullet'             => $parsed['cv_bullet'],
                'portfolio_description' => $parsed['portfolio_description'],
                'linkedin_post'         => $parsed['linkedin_post'],
            ];
        } catch (\Exception $e) {
            $latencyMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            Log::error('AI Profile Suggestion failed', [
                'error'      => $e->getMessage(),
                'user_id'    => $user->id,
                'project_id' => $project->id,
            ]);

            $this->logUsage(
                userId: $user->id,
                promptTokens: 0,
                completionTokens: 0,
                latencyMs: $latencyMs,
                status: AiUsageLog::STATUS_ERROR,
                errorMessage: $e->getMessage(),
            );

            return ['cv_bullet' => '', 'portfolio_description' => '', 'linkedin_post' => '', 'error' => true];
        }
    }

    private function buildProjectContext(User $user, Project $project): array
    {
        $memberRole = $project->members()
            ->where('user_id', $user->id)
            ->value('role') ?? $user->role;

        $durationWeeks = $project->completed_at
            ? (int) $project->created_at->diffInWeeks($project->completed_at)
            : 0;

        $techStack = is_array($project->tech_stack)
            ? implode(', ', $project->tech_stack)
            : ($project->tech_stack ?? 'not specified');

        $tasks = Task::where('project_id', $project->id)
            ->where('assigned_to', $user->id)
            ->where('status', 'done')
            ->limit(5)
            ->pluck('title')
            ->all();

        return [
            'role'          => $memberRole,
            'title'         => $project->title,
            'description'   => $project->description,
            'tech_stack'    => $techStack,
            'domain'        => $project->domain ?? 'general',
            'duration_weeks' => $durationWeeks,
            'tasks'         => $tasks,
        ];
    }

    private function buildPrompt(array $ctx): string
    {
        $tasksList = ! empty($ctx['tasks'])
            ? implode(', ', $ctx['tasks'])
            : 'not tracked';

        return <<<PROMPT
A developer just completed a project. Generate career content.
Developer role: {$ctx['role']}
Project: {$ctx['title']} — {$ctx['description']}
Tech stack: {$ctx['tech_stack']}
Duration: {$ctx['duration_weeks']} weeks
Tasks they did: {$tasksList}

Return JSON:
{
  "cv_bullet": "<one impactful bullet point starting with an action verb, under 30 words>",
  "portfolio_description": "<2-3 paragraph project description for a portfolio, 120-180 words>",
  "linkedin_post": "<casual but professional post, 80-120 words, no hashtag spam>"
}
PROMPT;
    }

    private function parseResponse(string $content): array
    {
        $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $content = preg_replace('/\s*```$/m', '', $content);
        $content = trim($content);

        $decoded = json_decode($content, associative: true);

        if ($decoded === null || ! is_array($decoded)) {
            throw new \RuntimeException('Failed to parse Claude response as JSON');
        }

        foreach (['cv_bullet', 'portfolio_description', 'linkedin_post'] as $field) {
            if (! isset($decoded[$field])) {
                throw new \RuntimeException("Missing required field: {$field}");
            }
        }

        return $decoded;
    }

    private function upsertSuggestion(User $user, Project $project, array $parsed, int $tokensUsed): void
    {
        try {
            AiSuggestion::updateOrCreate(
                [
                    'user_id'     => $user->id,
                    'source_type' => AiSuggestion::SOURCE_TYPE_PROJECT,
                    'source_id'   => $project->id,
                ],
                [
                    'cv_text'        => $parsed['cv_bullet'],
                    'portfolio_text' => $parsed['portfolio_description'],
                    'linkedin_text'  => $parsed['linkedin_post'],
                    'model'          => self::MODEL,
                    'tokens_used'    => $tokensUsed,
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to upsert AI suggestion', ['error' => $e->getMessage()]);
        }
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
                'feature'           => AiUsageLog::FEATURE_PROFILE_SUGGEST,
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
