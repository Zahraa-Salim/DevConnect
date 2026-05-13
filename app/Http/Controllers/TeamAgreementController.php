<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\TeamAgreement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamAgreementController extends Controller
{
    public const NDA_TEMPLATE = <<<'TEXT'
NON-DISCLOSURE AND CONFIDENTIALITY AGREEMENT

1. Definition of Confidential Information. For purposes of this Agreement, "Confidential Information" shall include all information or material that has or could have commercial value or other utility in the business in which Disclosing Party is engaged.

2. Exclusions from Confidential Information. Receiving Party's obligations under this Agreement do not extend to information that is: (a) publicly known at the time of disclosure or subsequently becomes publicly known through no fault of the Receiving Party; (b) discovered or created by the Receiving Party before disclosure by Disclosing Party.

3. Obligations of Receiving Party. Receiving Party shall hold and maintain the Confidential Information in strictest confidence for the sole and exclusive benefit of the Disclosing Party.

4. Time Periods. The nondisclosure provisions of this Agreement shall survive the termination of this Agreement and Receiving Party's duty to hold Confidential Information in confidence shall remain in effect until the Confidential Information no longer qualifies as a trade secret.
TEXT;

    public function showNda(Project $project): Response|RedirectResponse
    {
        abort_if($project->type !== Project::TYPE_REAL_CLIENT, 404);

        $alreadySigned = $this->hasSigned($project, TeamAgreement::TYPE_NDA);
        if ($alreadySigned) {
            return redirect()
                ->route('projects.show', $project)
                ->with('info', 'You have already signed the NDA for this project');
        }

        $project->load('owner:id,name');

        return Inertia::render('Projects/NdaSign', [
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
                'description' => $project->description,
                'type' => $project->type,
                'owner' => ['name' => $project->owner->name],
            ],
            'ndaText' => self::NDA_TEMPLATE,
            'agreementText' => $this->agreementText($project),
            'user' => [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
            ],
            'currentDate' => now()->format('F j, Y'),
        ]);
    }

    public function signNda(Request $request, Project $project): RedirectResponse
    {
        abort_if($project->type !== Project::TYPE_REAL_CLIENT, 404);
        abort_if($this->hasSigned($project, TeamAgreement::TYPE_NDA), 409);

        $rules = [
            'nda_agreed' => ['required', 'accepted'],
            'document_text' => ['nullable', 'string'],
        ];

        $agreementText = $this->agreementText($project);
        if ($agreementText) {
            $rules['agreement_agreed'] = ['required', 'accepted'];
        }

        $validated = $request->validate($rules);

        TeamAgreement::create([
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'type' => TeamAgreement::TYPE_NDA,
            'document_text' => $validated['document_text'] ?? self::NDA_TEMPLATE,
            'signed_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        ProjectMember::where('project_id', $project->id)
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->where('access_level', '<', 1)
            ->update(['access_level' => 1]);

        if ($agreementText && ! $this->hasSigned($project, TeamAgreement::TYPE_TEAM_AGREEMENT)) {
            TeamAgreement::create([
                'project_id' => $project->id,
                'user_id' => auth()->id(),
                'type' => TeamAgreement::TYPE_TEAM_AGREEMENT,
                'document_text' => $agreementText,
                'signed_at' => now(),
                'ip_address' => $request->ip(),
            ]);
        }

        if ($token = session()->pull('pending_invite_token')) {
            return app(InviteLinkController::class)->accept($request, $token);
        }

        if (session()->pull('pending_application_project_id')) {
            return redirect()
                ->route('projects.show', $project)
                ->with('success', 'NDA signed. You can now apply.');
        }

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'NDA signed successfully');
    }

    public function showAgreement(Project $project): Response
    {
        abort_if($project->type !== Project::TYPE_REAL_CLIENT, 404);
        abort_unless($this->isActiveMember($project), 403);

        return Inertia::render('Projects/AgreementSign', [
            'project' => $project->only(['id', 'title', 'description', 'type']),
            'agreementText' => $this->agreementText($project),
            'user' => [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
            ],
            'currentDate' => now()->format('F j, Y'),
            'alreadySigned' => $this->hasSigned($project, TeamAgreement::TYPE_TEAM_AGREEMENT),
        ]);
    }

    public function signAgreement(Request $request, Project $project): RedirectResponse
    {
        abort_if($project->type !== Project::TYPE_REAL_CLIENT, 404);
        abort_unless($this->isActiveMember($project), 403);
        abort_if($this->hasSigned($project, TeamAgreement::TYPE_TEAM_AGREEMENT), 409);

        $validated = $request->validate([
            'agreement_agreed' => ['required', 'accepted'],
            'document_text' => ['nullable', 'string'],
        ]);

        TeamAgreement::create([
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'type' => TeamAgreement::TYPE_TEAM_AGREEMENT,
            'document_text' => $validated['document_text'] ?? $this->agreementText($project) ?? '',
            'signed_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Team agreement signed');
    }

    public function storeAgreementText(Request $request, Project $project): RedirectResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);
        abort_if($project->type !== Project::TYPE_REAL_CLIENT, 404);

        $validated = $request->validate([
            'agreement_text' => ['required', 'string', 'max:5000'],
        ]);

        TeamAgreement::updateOrCreate(
            [
                'project_id' => $project->id,
                'user_id' => $project->owner_id,
                'type' => TeamAgreement::TYPE_TEAM_AGREEMENT,
            ],
            [
                'document_text' => $validated['agreement_text'],
                'signed_at' => now(),
                'ip_address' => $request->ip(),
            ]
        );

        return back()->with('success', 'Team agreement text saved');
    }

    private function hasSigned(Project $project, string $type): bool
    {
        return TeamAgreement::where('project_id', $project->id)
            ->where('user_id', auth()->id())
            ->where('type', $type)
            ->exists();
    }

    private function isActiveMember(Project $project): bool
    {
        return $project->members()
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->exists();
    }

    private function agreementText(Project $project): ?string
    {
        return TeamAgreement::where('project_id', $project->id)
            ->where('user_id', $project->owner_id)
            ->where('type', TeamAgreement::TYPE_TEAM_AGREEMENT)
            ->latest('signed_at')
            ->value('document_text');
    }
}
