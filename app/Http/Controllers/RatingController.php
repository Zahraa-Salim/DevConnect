<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\CalculateUserReputationJob;
use App\Jobs\UpdateContributionDnaJob;
use App\Models\Project;
use App\Models\Rating;
use App\Models\User;
use App\Notifications\RatingsReceivedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RatingController extends Controller
{
    public function create(Project $project): Response
    {
        abort_unless($project->status === Project::STATUS_COMPLETED, 403);

        abort_unless(
            $project->members()
                ->where('user_id', auth()->id())
                ->where('status', 'active')
                ->exists(),
            403
        );

        // Coming from ratings submission — show endorsement step
        if (session('ratingsSubmitted')) {
            $membersToEndorse = $project->members()
                ->where('user_id', '!=', auth()->id())
                ->where('status', 'active')
                ->with(['user:id,name,avatar_url,role', 'user.skills:id,user_id,skill_name,proficiency'])
                ->get();

            return Inertia::render('Projects/Rate', [
                'project'          => $project->only('id', 'title'),
                'membersToRate'    => [],
                'alreadyRated'     => false,
                'showEndorsements' => true,
                'membersToEndorse' => $membersToEndorse,
            ]);
        }

        $ratedIds = Rating::where('rater_id', auth()->id())
            ->where('project_id', $project->id)
            ->pluck('rated_id')
            ->all();

        $membersToRate = $project->members()
            ->where('user_id', '!=', auth()->id())
            ->where('status', 'active')
            ->whereNotIn('user_id', $ratedIds)
            ->with(['user:id,name,avatar_url,role', 'user.skills:id,user_id,skill_name,proficiency'])
            ->get();

        return Inertia::render('Projects/Rate', [
            'project'          => $project->only('id', 'title'),
            'membersToRate'    => $membersToRate,
            'alreadyRated'     => ! empty($ratedIds) && $membersToRate->isEmpty(),
            'showEndorsements' => false,
            'membersToEndorse' => [],
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->status === Project::STATUS_COMPLETED, 403);

        abort_unless(
            $project->members()
                ->where('user_id', auth()->id())
                ->where('status', 'active')
                ->exists(),
            403
        );

        $validated = $request->validate([
            'ratings'                  => ['required', 'array', 'min:1'],
            'ratings.*.rated_id'       => ['required', 'integer', 'exists:users,id'],
            'ratings.*.communication'  => ['required', 'integer', 'min:1', 'max:5'],
            'ratings.*.reliability'    => ['required', 'integer', 'min:1', 'max:5'],
            'ratings.*.contribution'   => ['required', 'integer', 'min:1', 'max:5'],
            'ratings.*.comment'        => ['nullable', 'string', 'max:500'],
        ]);

        $authId = auth()->id();

        $ratableMemberIds = $project->members()
            ->where('user_id', '!=', $authId)
            ->where('status', 'active')
            ->pluck('user_id')
            ->all();

        $alreadyRatedIds = Rating::where('rater_id', $authId)
            ->where('project_id', $project->id)
            ->pluck('rated_id')
            ->all();

        foreach ($validated['ratings'] as $entry) {
            $ratedId = $entry['rated_id'];

            if ($ratedId === $authId) continue;
            if (! in_array($ratedId, $ratableMemberIds)) continue;
            if (in_array($ratedId, $alreadyRatedIds)) continue;

            $communication = $entry['communication'];
            $reliability   = $entry['reliability'];
            $contribution  = $entry['contribution'];

            Rating::create([
                'project_id'          => $project->id,
                'rater_id'            => $authId,
                'rated_id'            => $ratedId,
                'communication_score' => $communication,
                'reliability_score'   => $reliability,
                'contribution_score'  => $contribution,
                'overall_score'       => round(($communication + $reliability + $contribution) / 3, 2),
                'comment'             => $entry['comment'] ?? null,
            ]);
        }

        $ratedUserIds = collect($validated['ratings'])->pluck('rated_id')->unique();
        User::whereIn('id', $ratedUserIds)->each(function (User $u) {
            CalculateUserReputationJob::dispatch($u);
            $u->notify(new RatingsReceivedNotification());
        });

        // Refresh rater's own DNA (completed-project count may have changed)
        UpdateContributionDnaJob::dispatch(auth()->user());

        return redirect()
            ->route('projects.rate', $project->id)
            ->with('ratingsSubmitted', true);
    }
}
