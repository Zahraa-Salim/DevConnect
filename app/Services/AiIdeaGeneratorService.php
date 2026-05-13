<?php

declare(strict_types=1);

namespace App\Services;

use Anthropic\Client;
use Illuminate\Support\Facades\Log;

class AiIdeaGeneratorService
{
    private ?Client $client = null;

    public function __construct()
    {
        // client is lazily initialised on first use via getClient()
    }

    private function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = AnthropicClientFactory::make();
        }
        return $this->client;
    }

    public function generate(array $userProfile, array $formInput): array
    {
        $prompt = $this->buildPrompt($userProfile, $formInput);

        try {
            $response = $this->getClient()->messages->create(
                maxTokens: 1500,
                messages: [
                    ['role' => 'user', 'content' => $prompt],
                ],
                model: 'claude-sonnet-4-20250514',
            );

            $content = $response->content[0]->text;
            $usage = [
                'input_tokens' => $response->usage->input_tokens,
                'output_tokens' => $response->usage->output_tokens,
            ];

            $parsed = $this->parseResponse($content);

            return [
                'success' => true,
                'idea' => $parsed,
                'usage' => $usage,
            ];
        } catch (\Throwable $e) {
            Log::error('AI Idea Generation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function buildPrompt(array $userProfile, array $formInput): string
    {
        $role = $userProfile['role'] ?? 'exploring';
        $skills = $userProfile['skills'] ?? [];
        $skillsList = count($skills) > 0
            ? implode(', ', array_slice($skills, 0, 5))
            : 'various technologies';

        $weeklyHours = $formInput['weekly_hours'] ?? 5;
        $teamSize = $formInput['team_size'] ?? 3;
        $domainInterest = $formInput['domain_interest'] ?? '';
        $extraInterests = $formInput['interests'] ?? '';

        $prompt = <<<PROMPT
You are a project idea generator for DevConnect LB, a platform for Lebanese computer science students to collaborate on coding projects.

Generate ONE project idea tailored to this user's profile and interests:

**User Profile:**
- Role: $role (can be: developer, designer, project manager, mentor, or exploring)
- Skills: $skillsList
- Available time: ~$weeklyHours hours per week
- Ideal team size: $teamSize people

**User Interests:**
- Domain of interest: $domainInterest
- Additional interests: $extraInterests

Generate a project idea that:
1. Matches the user's skill level and current skills
2. Can be completed in 4-8 weeks with $weeklyHours hours/week commitment
3. Is appropriate for a team of $teamSize people
4. Is interesting to someone in the $role role
5. Relates to the domain/interests they specified
6. Is practical and achievable for Lebanese CS students

Return ONLY a valid JSON object (no markdown, no backticks, no additional text) with this exact structure:
{
  "title": "string (max 200 chars, engaging and clear)",
  "description": "string (3-4 paragraphs: problem statement, what to build, why it matters, expected outcomes)",
  "domain": "string (1-3 words, the primary domain)",
  "difficulty": "beginner|intermediate|advanced",
  "team_size": number (3-5 recommended),
  "suggested_roles": ["Role 1", "Role 2", "Role 3"],
  "requirements": ["Requirement 1", "Requirement 2", "Requirement 3", "Requirement 4"]
}

Generate now:
PROMPT;

        return $prompt;
    }

    private function parseResponse(string $content): array
    {
        // Strip markdown code fences if present
        $content = preg_replace('/^```(?:json)?\s*/', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);
        $content = trim($content);

        $decoded = json_decode($content, associative: true);

        if ($decoded === null) {
            throw new \RuntimeException('Failed to parse Claude API response as JSON');
        }

        // Validate required fields
        $required = ['title', 'description', 'domain', 'difficulty', 'team_size', 'suggested_roles', 'requirements'];
        foreach ($required as $field) {
            if (!isset($decoded[$field])) {
                throw new \RuntimeException("Missing required field: $field");
            }
        }

        return $decoded;
    }
}
