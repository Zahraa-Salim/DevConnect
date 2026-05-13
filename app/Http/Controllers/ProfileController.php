<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SkillEndorsement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(User $user): Response
    {
        $user->load([
            'skills',
            'workingStyle',
            'ratingsReceived',
            'aiSuggestions',
            'projectMemberships' => fn($q) => $q->with('project:id,title,status,tech_stack,updated_at'),
            'contributionLogs' => fn($q) => $q->where('status', 'merged')->with('issue'),
        ]);

        $isOwner = auth()->id() === $user->id;

        $endorsements = SkillEndorsement::where('endorsed_id', $user->id)
            ->select('skill_name', DB::raw('count(*) as count'))
            ->groupBy('skill_name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($e) => ['skill' => $e->skill_name, 'count' => (int) $e->count])
            ->values()
            ->all();

        return Inertia::render('Profile/Show', [
            'user'         => $user,
            'isOwner'      => $isOwner,
            'endorsements' => $endorsements,
        ]);
    }
}
