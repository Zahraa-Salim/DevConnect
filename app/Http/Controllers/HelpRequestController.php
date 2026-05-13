<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\HelpRequest;
use App\Models\HelpRequestComment;
use App\Models\MentorProfile;
use App\Notifications\HelpRequestClaimedNotification;
use App\Notifications\HelpRequestPostedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HelpRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $query = HelpRequest::with(['user:id,name,avatar_url,role'])
            ->whereIn('status', [HelpRequest::STATUS_PENDING, HelpRequest::STATUS_ACCEPTED])
            ->withCount('comments')
            ->orderByDesc('created_at');

        if ($tag = $request->query('tag')) {
            $query->whereJsonContains('tech_tags', $tag);
        }

        $requests = $query->paginate(15);
        $myRequests = HelpRequest::where('user_id', auth()->id())
            ->withCount('comments')
            ->orderByDesc('created_at')
            ->get();

        $mentorProfile = auth()->user()->mentorProfile;
        $isApprovedMentor = $mentorProfile && $mentorProfile->status === MentorProfile::STATUS_APPROVED;

        return Inertia::render('Help/Index', [
            'requests' => $requests,
            'myRequests' => $myRequests,
            'isApprovedMentor' => $isApprovedMentor,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:2000'],
            'tech_tags' => ['required', 'array', 'min:1'],
            'tech_tags.*' => ['string', 'max:50'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
        ]);

        $helpRequest = HelpRequest::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'tech_tags' => $validated['tech_tags'],
            'project_id' => $validated['project_id'] ?? null,
            'status' => HelpRequest::STATUS_PENDING,
        ]);

        MentorProfile::where('status', MentorProfile::STATUS_APPROVED)
            ->where('is_active', true)
            ->get()
            ->filter(fn ($p) => ! empty(array_intersect($p->focus_areas ?? [], $validated['tech_tags'])))
            ->each(fn ($p) => $p->user?->notify(new HelpRequestPostedNotification($helpRequest)));

        return redirect()->back()->with('success', 'Help request posted.');
    }

    public function storeComment(Request $request, HelpRequest $helpRequest): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $helpRequest->comments()->create([
            'user_id' => auth()->id(),
            'body' => $validated['body'],
        ]);

        return redirect()->back()->with('success', 'Comment posted.');
    }

    public function comments(HelpRequest $helpRequest): JsonResponse
    {
        return response()->json(
            $helpRequest->comments()
                ->with('user:id,name,avatar_url,role')
                ->get()
        );
    }

    public function destroyComment(HelpRequest $helpRequest, HelpRequestComment $comment): RedirectResponse
    {
        abort_if($comment->user_id !== auth()->id() && ! auth()->user()->is_admin, 403);
        $comment->delete();

        return redirect()->back()->with('success', 'Comment deleted.');
    }

    public function startDm(HelpRequest $helpRequest): RedirectResponse
    {
        $posterId = $helpRequest->user_id;
        $authId = auth()->id();

        abort_if($posterId === $authId, 422, 'You cannot message yourself.');

        $existing = Conversation::where('type', Conversation::TYPE_DM)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $authId))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $posterId))
            ->first();

        if ($existing) {
            return redirect()->route('messages.show', $existing->id);
        }

        $dm = Conversation::create([
            'type' => Conversation::TYPE_DM,
            'thread_type' => Conversation::THREAD_MAIN,
            'last_message_at' => now(),
        ]);

        $dm->participants()->attach([$authId, $posterId]);

        return redirect()->route('messages.show', $dm->id);
    }

    public function claim(Request $request, HelpRequest $helpRequest): JsonResponse
    {
        $profile = auth()->user()->mentorProfile;

        abort_if(
            ! $profile || $profile->status !== MentorProfile::STATUS_APPROVED,
            403,
            'Only approved mentors can claim help requests.'
        );

        abort_if($helpRequest->status !== HelpRequest::STATUS_PENDING, 422, 'This request is no longer open.');

        $helpRequest->update([
            'status' => HelpRequest::STATUS_ACCEPTED,
            'mentor_id' => auth()->id(),
        ]);

        $helpRequest->user?->notify(new HelpRequestClaimedNotification($helpRequest));

        return response()->json(['success' => true]);
    }

    public function resolve(Request $request, HelpRequest $helpRequest): JsonResponse
    {
        $authId = auth()->id();
        $profile = auth()->user()->mentorProfile;
        $isRequester = $helpRequest->user_id === $authId;
        $isMentor = $profile && $profile->status === MentorProfile::STATUS_APPROVED;

        abort_if(! $isRequester && ! $isMentor, 403, 'Not authorised to resolve this request.');

        $helpRequest->update(['status' => HelpRequest::STATUS_RESPONDED]);

        return response()->json(['success' => true]);
    }
}
