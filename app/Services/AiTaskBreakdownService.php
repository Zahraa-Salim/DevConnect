<?php

declare(strict_types=1);

namespace App\Services;

use Anthropic\Anthropic;
use App\Models\Project;
use Illuminate\Support\Facades\Log;

class AiTaskBreakdownService
{
    private Anthropic $client;

    public function __construct()
    {
        $apiKey = env('ANTHROPIC_API_KEY');
        if (!$apiKey || str_starts_with($apiKey, 'sk-ant-xxxxx')) {
            throw new \RuntimeException('ANTHROPIC_API_KEY is not configured.');
        }
        $this->client = new Anthropic(['apiKey' => $apiKey]);
    }

    public function generate(Project $project): array
    {
        $project->loadMissing('roles');
        $techStack = $project->tech_stack ? implode(', ', $project->tech_stack) : 'not specified';
        $roles = $project->roles->pluck('role_name')->implode(', ') ?: 'Developer, Designer, Project Manager';

        $prompt = "You are generating a task breakdown for a software project on DevConnect LB.

Project Title: {$project->title}
Project Description: {$project->description}
Tech Stack: {$techStack}
Team Roles: {$roles}
Domain: {$project->domain}

Generate 10-15 tasks for the product backlog. The team uses Scrum methodology.

Each task MUST have these exact fields:
- title: short actionable title (max 100 characters, as a user story or technical task)
- description: 1-2 sentence description of what to do
- role_tag: which role should handle this (one of: {$roles})
- energy: one of \"quick_win\" (under 1 hour), \"deep_work\" (2+ hours), \"blocked\" (waiting on dependency)
- priority: one of \"low\", \"medium\", \"high\"
- story_points: estimated effort using Fibonacci (one of: 1, 2, 3, 5, 8, 13). 1=trivial, 3=small feature, 5=medium feature, 8=large feature, 13=epic.

Order tasks by priority (high first). Mix story point sizes. Include setup tasks (1-2 points), feature tasks (3-8 points), and integration/testing tasks (2-5 points).

Return ONLY a JSON array. No markdown. No explanation.";

        try {
            $response = $this->client->messages->create([
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 3000,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            $content = $response->content[0]->text;
            $usage = [
                'input_tokens' => $response->usage->input_tokens,
                'output_tokens' => $response->usage->output_tokens,
            ];

            $tasks = $this->parseResponse($content);

            return [
                'success' => true,
                'tasks' => $tasks,
                'usage' => $usage,
            ];
        } catch (\Exception $e) {
            Log::error('AI Task Breakdown failed', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
                'user_id' => auth()->id(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function parseResponse(string $content): array
    {
        $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $content = preg_replace('/\s*```$/m', '', $content);
        $content = trim($content);

        $decoded = json_decode($content, associative: true);

        if ($decoded === null || !is_array($decoded)) {
            throw new \RuntimeException('Failed to parse Claude API response as JSON array');
        }

        return $decoded;
    }
}
