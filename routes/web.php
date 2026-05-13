<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GitHubAuthController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\CommunityIdeaController;
use App\Http\Controllers\AiIdeaController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\InviteLinkController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\ProjectRoleController;
use App\Http\Controllers\TeamAgreementController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\DecisionLogController;
use App\Http\Controllers\AliveSignalController;
use App\Http\Controllers\ProjectFileController;
use App\Http\Controllers\GitHubController;
use App\Http\Controllers\ContributeController;
use App\Http\Controllers\ContributionLogController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SkillEndorsementController;
use App\Http\Controllers\AiSuggestionController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\MentorSlotController;
use App\Http\Controllers\HelpRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\IdeaController as AdminIdeaController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\MentorAdminController;
use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Onboarding\ProfileController as OnboardingProfileController;
use App\Http\Controllers\Onboarding\QuizController;
use App\Models\User;
use App\Models\Project;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public
Route::get('/', fn () => Inertia::render('Landing'))->name('landing');
Route::get('/invite/{token}', [InviteLinkController::class, 'show'])->name('invite.show');

// Guest-only auth pages
Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => Inertia::render('Login'))->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', fn () => Inertia::render('Register'))->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// GitHub OAuth (not restricted to guest, handles both new and existing users)
Route::get('/auth/github', [GitHubAuthController::class, 'redirect'])
    ->name('auth.github');
Route::get('/auth/github/callback', [GitHubAuthController::class, 'callback'])
    ->name('auth.github.callback');

// Auth-only routes available during onboarding
Route::middleware(['auth', 'suspended'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Onboarding (post-register)
    Route::get('/onboarding/quiz', [QuizController::class, 'show'])
        ->name('onboarding.quiz');
    Route::post('/onboarding/quiz', [QuizController::class, 'store']);

    Route::get('/onboarding/profile', [OnboardingProfileController::class, 'show'])
        ->name('onboarding.profile');
    Route::post('/onboarding/profile', [OnboardingProfileController::class, 'update']);
});

// Main authenticated routes require completed onboarding
Route::middleware(['auth', 'suspended', 'onboarding'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', fn () => redirect()->route('profile.show', auth()->id()))->name('profile');
    Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Public idea browse (any logged-in user)
    Route::get('/ideas', [IdeaController::class, 'index'])->name('ideas.index');

    // AI Idea generation (must come BEFORE /{idea} route)
    Route::post('/ideas/generate', [AiIdeaController::class, 'generate'])->name('ideas.generate');

    Route::get('/ideas/{idea}', [IdeaController::class, 'show'])->name('ideas.show');

    // Projects routes (must come before /{project} routes)
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::patch('/projects/{project}/status', [ProjectController::class, 'updateStatus'])->name('projects.updateStatus');
    Route::get('/projects/{project}/readiness', [ProjectController::class, 'readiness'])->name('projects.readiness');
    Route::post('/projects/{project}/invite-links', [InviteLinkController::class, 'store'])->name('projects.inviteLinks.store');
    Route::get('/projects/{project}/invite-links', [InviteLinkController::class, 'index'])->name('projects.inviteLinks.index');
    Route::delete('/projects/{project}/invite-links/{inviteLink}', [InviteLinkController::class, 'destroy'])->name('projects.inviteLinks.destroy');
    Route::post('/invite/{token}/accept', [InviteLinkController::class, 'accept'])->name('invite.accept');
    Route::get('/projects/{project}/nda', [TeamAgreementController::class, 'showNda'])->name('projects.nda');
    Route::post('/projects/{project}/nda/sign', [TeamAgreementController::class, 'signNda'])->name('projects.nda.sign');
    Route::get('/projects/{project}/agreement', [TeamAgreementController::class, 'showAgreement'])->name('projects.agreement');
    Route::post('/projects/{project}/agreement/sign', [TeamAgreementController::class, 'signAgreement'])->name('projects.agreement.sign');
    Route::post('/projects/{project}/agreement/text', [TeamAgreementController::class, 'storeAgreementText'])->name('projects.agreement.text');
    Route::post('/projects/{project}/milestones', [MilestoneController::class, 'store'])->name('projects.milestones.store');
    Route::patch('/projects/{project}/milestones/{milestone}/complete', [MilestoneController::class, 'complete'])->name('projects.milestones.complete');
    Route::delete('/projects/{project}/milestones/{milestone}', [MilestoneController::class, 'destroy'])->name('projects.milestones.destroy');

    // Applications routes
    Route::post('/projects/{project}/apply', [ApplicationController::class, 'store'])->name('projects.apply');
    Route::patch('/projects/{project}/applications/{application}/accept', [ApplicationController::class, 'accept'])->name('projects.applications.accept');
    Route::patch('/projects/{project}/applications/{application}/decline', [ApplicationController::class, 'decline'])->name('projects.applications.decline');
    Route::delete('/projects/{project}/applications/{application}', [ApplicationController::class, 'withdraw'])->name('projects.applications.withdraw');

    // Project Roles routes (owner only)
    Route::post('/projects/{project}/roles', [ProjectRoleController::class, 'store'])->name('projects.roles.store');
    Route::put('/projects/{project}/roles/{role}', [ProjectRoleController::class, 'update'])->name('projects.roles.update');
    Route::delete('/projects/{project}/roles/{role}', [ProjectRoleController::class, 'destroy'])->name('projects.roles.destroy');

    // Project Members routes
    Route::get('/projects/{project}/rate', [RatingController::class, 'create'])->name('projects.rate');
    Route::post('/projects/{project}/ratings', [RatingController::class, 'store'])->name('projects.ratings.store');
    Route::post('/projects/{project}/endorsements', [SkillEndorsementController::class, 'store'])->name('projects.endorsements.store');
    Route::get('/projects/{project}/suggestions', [AiSuggestionController::class, 'index'])->name('projects.suggestions');
    Route::post('/projects/{project}/suggestions', [AiSuggestionController::class, 'generate'])->name('projects.suggestions.generate');

    Route::post('/projects/{project}/leave', [ProjectController::class, 'leave'])->name('projects.leave');
    Route::delete('/projects/{project}/members/{member}', [ProjectController::class, 'removeMember'])->name('projects.members.remove');

    // Community idea actions
    Route::post('/ideas', [CommunityIdeaController::class, 'store'])->name('ideas.store');
    Route::post('/ideas/{idea}/vote', [CommunityIdeaController::class, 'toggleVote'])->name('ideas.vote');
    Route::post('/ideas/{idea}/comments', [CommunityIdeaController::class, 'storeComment'])->name('ideas.comments.store');
    Route::delete('/ideas/{idea}/comments/{comment}', [CommunityIdeaController::class, 'destroyComment'])->name('ideas.comments.destroy');

    // Messages routes
    Route::get('/messages', [ConversationController::class, 'index'])->name('messages.index');
    Route::post('/messages', [ConversationController::class, 'store'])->name('messages.store');
    Route::get('/messages/users/search', function () {
        $q = request()->query('q', '');
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        return User::where('id', '!=', auth()->id())
            ->where('name', 'LIKE', "%{$q}%")
            ->select('id', 'name', 'avatar_url', 'role')
            ->limit(10)
            ->get();
    })->name('messages.users.search');
    Route::get('/messages/{conversation}', [ConversationController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}/messages', [MessageController::class, 'store'])->name('messages.send');
    Route::put('/messages/msg/{message}', [MessageController::class, 'update'])->name('messages.update');
    Route::delete('/messages/msg/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
    Route::post('/messages/{conversation}/typing', [MessageController::class, 'typing'])->name('messages.typing');

    // Sprints
    Route::get('/projects/{project}/sprints', [SprintController::class, 'index'])->name('projects.sprints.index');
    Route::post('/projects/{project}/sprints', [SprintController::class, 'store'])->name('projects.sprints.store');
    Route::put('/projects/{project}/sprints/{sprint}', [SprintController::class, 'update'])->name('projects.sprints.update');
    Route::delete('/projects/{project}/sprints/{sprint}', [SprintController::class, 'destroy'])->name('projects.sprints.destroy');
    Route::patch('/projects/{project}/sprints/{sprint}/start', [SprintController::class, 'start'])->name('projects.sprints.start');
    Route::patch('/projects/{project}/sprints/{sprint}/complete', [SprintController::class, 'complete'])->name('projects.sprints.complete');

    // Tasks — generate must come before {task} to avoid route collision
    Route::get('/projects/{project}/tasks', [TaskController::class, 'index'])->name('projects.tasks.index');
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])->name('projects.tasks.store');
    Route::post('/projects/{project}/tasks/generate', [TaskController::class, 'generateAiTasks'])->name('projects.tasks.generate');
    Route::get('/projects/{project}/tasks/{task}', [TaskController::class, 'show'])->name('projects.tasks.show');
    Route::put('/projects/{project}/tasks/{task}', [TaskController::class, 'update'])->name('projects.tasks.update');
    Route::delete('/projects/{project}/tasks/{task}', [TaskController::class, 'destroy'])->name('projects.tasks.destroy');
    Route::patch('/projects/{project}/tasks/{task}/move', [TaskController::class, 'move'])->name('projects.tasks.move');
    Route::patch('/projects/{project}/tasks/{task}/assign-sprint', [TaskController::class, 'assignToSprint'])->name('projects.tasks.assignToSprint');
    Route::patch('/projects/{project}/tasks/{task}/remove-sprint', [TaskController::class, 'removeFromSprint'])->name('projects.tasks.removeFromSprint');

    // Decision Log
    Route::get('/projects/{project}/decisions', [DecisionLogController::class, 'index'])->name('projects.decisions.index');
    Route::post('/projects/{project}/decisions', [DecisionLogController::class, 'store'])->name('projects.decisions.store');
    Route::delete('/projects/{project}/decisions/{decision}', [DecisionLogController::class, 'destroy'])->name('projects.decisions.destroy');

    // Alive Signals
    Route::post('/projects/{project}/alive', [AliveSignalController::class, 'store'])->name('projects.alive.store');
    Route::get('/projects/{project}/alive', [AliveSignalController::class, 'index'])->name('projects.alive.index');

    // Project Files
    Route::get('/projects/{project}/files', [ProjectFileController::class, 'index'])->name('projects.files.index');
    Route::post('/projects/{project}/files', [ProjectFileController::class, 'store'])->name('projects.files.store');
    Route::get('/projects/{project}/files/{file}/download', [ProjectFileController::class, 'download'])->name('projects.files.download');
    Route::delete('/projects/{project}/files/{file}', [ProjectFileController::class, 'destroy'])->name('projects.files.destroy');

    // Contribute / GitHub Issues
    Route::get('/contribute', [ContributeController::class, 'index'])->name('contribute.index');
    Route::post('/contribute/rank', [ContributeController::class, 'aiRank'])->name('contribute.rank');
    Route::post('/contribution-logs', [ContributionLogController::class, 'store'])->name('contribution-logs.store');
    Route::patch('/contribution-logs/{log}', [ContributionLogController::class, 'updateStatus'])->name('contribution-logs.update');
    Route::post('/contribution-logs/{log}/convert', [ContributionLogController::class, 'convertToProject'])->name('contribution-logs.convert');

    // GitHub integration
    Route::post('/projects/{project}/github/link', [GitHubController::class, 'linkRepo'])->name('projects.github.link');
    Route::delete('/projects/{project}/github/unlink', [GitHubController::class, 'unlinkRepo'])->name('projects.github.unlink');
    Route::get('/projects/{project}/github/commits', [GitHubController::class, 'commits'])->name('projects.github.commits');
    Route::get('/projects/{project}/github/pulls', [GitHubController::class, 'pullRequests'])->name('projects.github.pulls');
    Route::get('/projects/{project}/github/contributors', [GitHubController::class, 'contributors'])->name('projects.github.contributors');

    // Mentor system
    Route::get('/mentor/apply', [MentorController::class, 'apply'])->name('mentor.apply');
    Route::post('/mentor/apply', [MentorController::class, 'store'])->name('mentor.store');
    Route::get('/mentor/dashboard', [MentorController::class, 'dashboard'])->name('mentor.dashboard');
    Route::post('/mentor/availability', [MentorController::class, 'availability'])->name('mentor.availability');
    Route::get('/mentors', [MentorController::class, 'directory'])->name('mentor.directory');

    // Mentor slots
    Route::post('/mentor-slots/{slot}/book', [MentorSlotController::class, 'book'])->name('mentor-slots.book');
    Route::delete('/mentor-slots/{slot}/book', [MentorSlotController::class, 'cancel'])->name('mentor-slots.cancel');

    // Help requests (community peer-help board)
    Route::get('/help-requests', [HelpRequestController::class, 'index'])->name('help-requests.index');
    Route::post('/help-requests', [HelpRequestController::class, 'store'])->name('help-requests.store');
    Route::get('/help-requests/{helpRequest}/comments', [HelpRequestController::class, 'comments'])->name('help-requests.comments.index');
    Route::post('/help-requests/{helpRequest}/comments', [HelpRequestController::class, 'storeComment'])->name('help-requests.comments.store');
    Route::delete('/help-requests/{helpRequest}/comments/{comment}', [HelpRequestController::class, 'destroyComment'])->name('help-requests.comments.destroy');
    Route::post('/help-requests/{helpRequest}/dm', [HelpRequestController::class, 'startDm'])->name('help-requests.dm');
    Route::post('/help-requests/{helpRequest}/claim', [HelpRequestController::class, 'claim'])->name('help-requests.claim');
    Route::post('/help-requests/{helpRequest}/resolve', [HelpRequestController::class, 'resolve'])->name('help-requests.resolve');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::post('/notifications/{id}/unread', [NotificationController::class, 'markUnread'])->name('notifications.unread');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    // Group chat endpoint for projects
    Route::get('/projects/{project}/chat', function (Project $project) {
        $conversation = Conversation::where('project_id', $project->id)
            ->where('type', 'group')
            ->where('thread_type', 'main')
            ->firstOrFail();

        abort_unless(
            ConversationParticipant::where('conversation_id', $conversation->id)
                ->where('user_id', auth()->id())
                ->exists(),
            403
        );

        // Update last_read_at
        ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', auth()->id())
            ->update(['last_read_at' => now()]);

        $messages = $conversation->messages()
            ->with('sender:id,name,avatar_url')
            ->orderBy('created_at', 'asc')
            ->paginate(50, paginate: false);

        $participants = $conversation->participants()->select('users.id', 'users.name', 'users.avatar_url')->get();

        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages,
            'participants' => $participants,
        ]);
    })->name('projects.chat');
});

// Public profile (no auth required)
Route::get('/profile/{user}', [ProfileController::class, 'show'])->name('profile.show');

// Admin routes
Route::middleware(['auth', 'suspended', 'onboarding', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/unsuspend', [AdminUserController::class, 'unsuspend'])->name('users.unsuspend');

        Route::get('/mentors', [MentorAdminController::class, 'index'])->name('mentors.index');
        Route::post('/mentors/{mentor}/approve', [MentorAdminController::class, 'approve'])->name('mentors.approve');
        Route::post('/mentors/{mentor}/reject', [MentorAdminController::class, 'reject'])->name('mentors.reject');

        Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->name('analytics');

        Route::get('/ideas', [AdminIdeaController::class, 'index'])->name('ideas.index');
        Route::get('/ideas/create', [AdminIdeaController::class, 'create'])->name('ideas.create');
        Route::post('/ideas', [AdminIdeaController::class, 'store'])->name('ideas.store');
        Route::get('/ideas/{idea}/edit', [AdminIdeaController::class, 'edit'])->name('ideas.edit');
        Route::put('/ideas/{idea}', [AdminIdeaController::class, 'update'])->name('ideas.update');
        Route::delete('/ideas/{idea}', [AdminIdeaController::class, 'destroy'])->name('ideas.destroy');
    });
