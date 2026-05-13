<?php

declare(strict_types=1);

namespace App\Services;

use Anthropic\Anthropic;
use App\Models\AiUsageLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AiChemistryService
{
    private const MODEL = 'claude-sonnet-4-20250514';

    private Anthropic $client;

    public function __construct()
    {
        $apiKey = env('ANTHROPIC_API_KEY');
        if (! $apiKey || str_starts_with($apiKey, 'sk-ant-xxxxx')) {
            throw new \RuntimeException('ANTHROPIC_API_KEY is not configured.');
        }
        $this->client = new Anthropic(['apiKey' => $apiKey]);
    }

    public function analyze(Project $project, User $applicant): array
    {
        $cacheKey = "chemistry_{$project->id}_{$applicant->id}";

        return Cache::remember($cacheKey, 86400, function () use ($project, $applicant) {
            return $this->callClaude($project, $applicant);
        });
    }

    private function callClaude(Project $project, User $applicant): array
    {
        $teamMembers = $project->members()
            ->where('status', 'active')
            ->with(['user:id,name,role', 'user.workingStyle', 'user.skills'])
            ->get();

        $applicant->load(['workingStyle', 'skills']);

        $teamStyles = $teamMembers->map(function ($m) {
            $ws = $m->user?->workingStyle;
            return [
                'name'              => $m->user?->name,
                'role'              => $m->user?->role,
                'communication'     => $ws?->communication_pref,
                'feedback_style'    => $ws?->feedback_style,
                'weekly_hours'      => $ws?->weekly_hours,
            ];
        })->values()->toArray();

        $applicantStyle = [
            'communication'  => $applicant->workingStyle?->communication_pref,
            'feedback_style' => $applicant->workingStyle?->feedback_style,
            'weekly_hours'   => $applicant->workingStyle?->weekly_hours,
        ];

        $skills       = $applicant->skills->pluck('skill_name')->join(', ') ?: 'unspecified';
        $currentRoles = $teamMembers->pluck('role')->filter()->join(', ') ?: 'none yet';
        $openRoles    = $project->roles()->where('is_open', true)->pluck('role_name')->join(', ') ?: 'open';

        $prompt = <<<PROMPT
Analyze team chemistry for adding this applicant.
Team working styles: {$this->encode($teamStyles)}
Applicant working style: {$this->encode($applicantStyle)}
Applicant skills: {$skills}, role: {$applicant->role}
Team current roles: {$currentRoles}
Return JSON: {"label": "Strong Fit|Good Fit|Possible Fit|Challenging", "alignment": [...], "friction": [...], "summary": "..."}
PROMPT;

        $startTime = hrtime(true);

        try {
            $response = $this->client->messages->create([
                'model'      => self::MODEL,
                'max_tokens' => 400,
                'system'     => 'You are a team dynamics expert. Respond ONLY with valid JSON.',
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            $latencyMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            $this->logUsage(
                projectId: $project->id,
                promptTokens: $response->usage->input_tokens,
                completionTokens: $response->usage->output_tokens,
                latencyMs: $latencyMs,
                status: AiUsageLog::STATUS_SUCCESS,
            );

            return $this->parse($response->content[0]->text);
        } catch (\Throwable $e) {
            $latencyMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            Log::error('AiChemistryService failed', [
                'project_id'   => $project->id,
                'applicant_id' => $applicant->id,
                'error'        => $e->getMessage(),
            ]);

            $this->logUsage(
                projectId: $project->id,
                promptTokens: 0,
                completionTokens: 0,
                latencyMs: $latencyMs,
                status: AiUsageLog::STATUS_ERROR,
                errorMessage: $e->getMessage(),
            );

            return ['label' => 'Unknown', 'alignment' => [], 'friction' => [], 'summary' => 'Analysis unavailable'];
        }
    }

    private function parse(string $content): array
    {
        $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $content = preg_replace('/\s*```$/m', '', $content);
        $content = trim($content);

        $decoded = json_decode($content, associative: true);

        if (! is_array($decoded)) {
            return ['label' => 'Unknown', 'alignment' => [], 'friction' => [], 'summary' => 'Analysis unavailable'];
        }

        return [
            'label'     => $decoded['label']     ?? 'Unknown',
            'alignment' => $decoded['alignment'] ?? [],
            'friction'  => $decoded['friction']  ?? [],
            'summary'   => $decoded['summary']   ?? '',
        ];
    }

    private function encode(mixed $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function logUsage(
        int $projectId,
        int $promptTokens,
        int $completionTokens,
        int $latencyMs,
        string $status,
        ?string $errorMessage = null,
    ): void {
        try {
            AiUsageLog::create([
                'project_id'        => $projectId,
                'feature'           => AiUsageLog::FEATURE_CHEMISTRY,
                'model'             => self::MODEL,
                'prompt_tokens'     => $promptTokens,
                'completion_tokens' => $completionTokens,
                'latency_ms'        => $latencyMs,
                'status'            => $status,
                'error_message'     => $errorMessage,
                'created_at'        => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to write AI usage log', ['error' => $e->getMessage()]);
        }
    }
}
