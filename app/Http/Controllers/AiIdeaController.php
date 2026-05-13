<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiUsageLog;
use App\Models\ProjectIdea;
use App\Services\AiIdeaGeneratorService;
use Illuminate\Http\Request;

class AiIdeaController extends Controller
{
    public function generate(Request $request)
    {
        try {
            $service = app(AiIdeaGeneratorService::class);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => 'AI service is not configured. Please contact the administrator.',
            ], 503);
        }

        // Validate inputs
        $validated = $request->validate([
            'domain_interest' => ['nullable', 'string', 'max:80'],
            'team_size' => ['nullable', 'integer', 'min:1', 'max:20'],
            'weekly_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
            'interests' => ['nullable', 'string', 'max:500'],
        ]);

        // Check rate limit
        $recentCount = AiUsageLog::where('user_id', auth()->id())
            ->where('feature', AiUsageLog::FEATURE_IDEA_GEN)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentCount >= 10) {
            return response()->json([
                'success' => false,
                'error' => 'You\'ve reached the limit of 10 generations per hour. Please try again later.',
            ], 429);
        }

        // Gather user profile data
        $user = auth()->user()->load('skills:id,skill_name,proficiency');
        $userProfile = [
            'role' => $user->role,
            'skills' => $user->skills->pluck('skill_name')->toArray(),
            'github_username' => $user->github_username,
        ];

        $startTime = microtime(true);

        // Call AI service
        try {
            $result = $service->generate($userProfile, $validated);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Unknown error');
            }

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            // Log successful usage
            AiUsageLog::create([
                'user_id' => auth()->id(),
                'feature' => AiUsageLog::FEATURE_IDEA_GEN,
                'model' => 'claude-sonnet-4-20250514',
                'prompt_tokens' => $result['usage']['input_tokens'] ?? 0,
                'completion_tokens' => $result['usage']['output_tokens'] ?? 0,
                'latency_ms' => $latencyMs,
                'status' => AiUsageLog::STATUS_SUCCESS,
                'created_at' => now(),
            ]);

            // Save idea to database
            $idea = ProjectIdea::create([
                'source' => ProjectIdea::SOURCE_AI,
                'title' => $result['idea']['title'],
                'description' => $result['idea']['description'],
                'domain' => $result['idea']['domain'] ?? $validated['domain_interest'],
                'difficulty' => $result['idea']['difficulty'],
                'team_size' => $result['idea']['team_size'] ?? $validated['team_size'] ?? 3,
                'suggested_roles' => $result['idea']['suggested_roles'] ?? [],
                'requirements' => $result['idea']['requirements'] ?? [],
                'submitted_by' => auth()->id(),
                'status' => ProjectIdea::STATUS_ACTIVE,
                'upvotes' => 0,
                'comments_count' => 0,
            ]);

            // Return the generated idea as JSON
            return response()->json([
                'success' => true,
                'idea' => [
                    'id' => $idea->id,
                    'title' => $idea->title,
                    'description' => $idea->description,
                    'domain' => $idea->domain,
                    'difficulty' => $idea->difficulty,
                    'team_size' => $idea->team_size,
                    'source' => $idea->source,
                    'status' => $idea->status,
                    'upvotes' => $idea->upvotes,
                    'comments_count' => $idea->comments_count,
                    'suggested_roles' => $idea->suggested_roles,
                    'requirements' => $idea->requirements,
                ]
            ]);
        } catch (\Throwable $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            // Log error
            AiUsageLog::create([
                'user_id' => auth()->id(),
                'feature' => AiUsageLog::FEATURE_IDEA_GEN,
                'model' => 'claude-sonnet-4-20250514',
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'latency_ms' => $latencyMs,
                'status' => AiUsageLog::STATUS_ERROR,
                'error_message' => $e->getMessage(),
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate idea. Please try again or contact support.',
            ], 500);
        }
    }
}
