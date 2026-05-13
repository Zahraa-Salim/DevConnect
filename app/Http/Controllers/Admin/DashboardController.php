<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\MentorProfile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $aiMonth = AiUsageLog::where('created_at', '>=', now()->startOfMonth());

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_users'       => User::count(),
                'new_users_week'    => User::where('created_at', '>=', now()->startOfWeek())->count(),
                'total_projects'    => Project::count(),
                'active_projects'   => Project::where('status', 'active')->count(),
                'ai_calls_month'    => (clone $aiMonth)->count(),
                'tokens_used_month' => (clone $aiMonth)->sum(DB::raw('prompt_tokens + completion_tokens')),
                'pending_mentors'   => MentorProfile::where('status', MentorProfile::STATUS_PENDING)->count(),
            ],
        ]);
    }
}
