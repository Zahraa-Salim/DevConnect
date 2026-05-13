<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommunityIdeaRequest;
use App\Models\IdeaComment;
use App\Models\IdeaVote;
use App\Models\ProjectIdea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommunityIdeaController extends Controller
{
    public function store(StoreCommunityIdeaRequest $request)
    {
        $validated = $request->validated();

        ProjectIdea::create([
            'source' => ProjectIdea::SOURCE_COMMUNITY,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'domain' => $validated['domain'] ?? null,
            'difficulty' => $validated['difficulty'],
            'team_size' => $validated['team_size'] ?? null,
            'suggested_roles' => $validated['suggested_roles'] ?? [],
            'requirements' => $validated['requirements'] ?? [],
            'submitted_by' => auth()->id(),
            'status' => ProjectIdea::STATUS_ACTIVE,
            'upvotes' => 0,
            'comments_count' => 0,
        ]);

        return redirect()->route('ideas.index')->with('success', 'Your idea has been submitted!');
    }

    public function toggleVote(ProjectIdea $idea)
    {
        $userId = auth()->id();

        DB::transaction(function () use ($idea, $userId) {
            $vote = IdeaVote::where('idea_id', $idea->id)
                ->where('user_id', $userId)
                ->first();

            if ($vote) {
                $vote->delete();
                $idea->decrement('upvotes');
            } else {
                IdeaVote::create([
                    'idea_id' => $idea->id,
                    'user_id' => $userId,
                ]);
                $idea->increment('upvotes');
            }
        });

        return back(fallback: route('ideas.index'));
    }

    public function storeComment(Request $request, ProjectIdea $idea)
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        IdeaComment::create([
            'idea_id' => $idea->id,
            'user_id' => auth()->id(),
            'body' => $validated['body'],
        ]);

        $idea->increment('comments_count');

        return back(fallback: route('ideas.show', $idea));
    }

    public function destroyComment(ProjectIdea $idea, IdeaComment $comment)
    {
        abort_unless($comment->user_id === auth()->id(), 403);

        $comment->delete();
        $idea->decrement('comments_count');

        return back(fallback: route('ideas.show', $idea));
    }
}
