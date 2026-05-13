<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiSuggestion;
use App\Models\Project;
use App\Services\AiProfileSuggestionService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AiSuggestionController extends Controller
{
    public function index(Project $project): Response
    {
        abort_unless(
            $project->members()
                ->where('user_id', auth()->id())
                ->where('status', 'active')
                ->exists(),
            403
        );

        $suggestion = AiSuggestion::where('user_id', auth()->id())
            ->where('source_type', AiSuggestion::SOURCE_TYPE_PROJECT)
            ->where('source_id', $project->id)
            ->latest()
            ->first();

        return Inertia::render('Projects/Suggestions', [
            'project'    => $project->only('id', 'title', 'status'),
            'suggestion' => $suggestion,
        ]);
    }

    public function generate(Project $project): JsonResponse
    {
        abort_unless($project->status === Project::STATUS_COMPLETED, 403);

        abort_unless(
            $project->members()
                ->where('user_id', auth()->id())
                ->where('status', 'active')
                ->exists(),
            403
        );

        try {
            $result = (new AiProfileSuggestionService())->generate(auth()->user(), $project);
        } catch (\RuntimeException $e) {
            return response()->json([
                'cv_bullet'             => '',
                'portfolio_description' => '',
                'linkedin_post'         => '',
                'error'                 => true,
            ], 503);
        }

        return response()->json([
            'cv_bullet'             => $result['cv_bullet'],
            'portfolio_description' => $result['portfolio_description'],
            'linkedin_post'         => $result['linkedin_post'],
            'error'                 => $result['error'] ?? false,
        ]);
    }
}
