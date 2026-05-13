<?php

namespace Database\Seeders;

use App\Models\AiSuggestion;
use App\Models\AliveSignal;
use App\Models\ChemistryScore;
use App\Models\DecisionLog;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectPulseLog;
use App\Models\Rating;
use App\Models\SkillEndorsement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds workspace data that makes every project tab feel alive:
 * Decision Log, Files, Alive Signals, Project Pulse, Ratings,
 * Skill Endorsements, Chemistry Scores, and AI Suggestions.
 *
 * Run after DemoProjectSeeder and DemoTaskSeeder.
 */
class DemoWorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        $sara     = User::where('email', DemoUserProfileSeeder::SARA_EMAIL)->firstOrFail();
        $teammates = User::whereIn('email', array_column(DemoUserProfileSeeder::TEAMMATES, 'email'))
            ->orderBy('id')->get()->values();

        $all = $teammates->prepend($sara)->unique('id')->values();

        foreach (DemoProjectSeeder::PROJECTS as $title => $meta) {
            $project = Project::where('title', $title)->where('owner_id', $sara->id)->firstOrFail();
            $members = $project->members()
                ->where('status', 'active')
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter()
                ->values();

            if ($members->isEmpty()) {
                $members = $all->take(3);
            }

            $this->seedDecisionLog($project, $members, $title);
            $this->seedAliveSignals($project, $members);
            $this->seedProjectPulse($project, $meta['status']);
            $this->seedFiles($project, $members, $title);

            if ($meta['status'] === Project::STATUS_COMPLETED) {
                $this->seedRatings($project, $members);
                $this->seedEndorsements($project, $members, $title);
                $this->seedAiSuggestions($project, $members, $sara, $title);
            }

            if (in_array($meta['status'], [Project::STATUS_ACTIVE, Project::STATUS_COMPLETED])) {
                $this->seedChemistryScore($project, $members);
            }
        }
    }

    // ── Decision Log ──────────────────────────────────────────────────────────

    private function seedDecisionLog(Project $project, $members, string $title): void
    {
        $decisions = $this->decisionsFor($title);
        $existing = DecisionLog::where('project_id', $project->id)->count();
        if ($existing >= count($decisions)) return;

        foreach ($decisions as $i => $d) {
            $author = $members[$i % $members->count()];
            DecisionLog::firstOrCreate(
                ['project_id' => $project->id, 'decision' => $d[0]],
                [
                    'user_id'    => $author->id,
                    'reason'     => $d[1],
                    'created_at' => Carbon::now()->subDays(count($decisions) - $i + 2),
                    'updated_at' => Carbon::now()->subDays(count($decisions) - $i + 2),
                ]
            );
        }
    }

    private function decisionsFor(string $title): array
    {
        return match ($title) {
            'Redesign Fintech Dashboard' => [
                ['Use Figma as the single source of truth for all design assets', 'Keeps handoff clean and prevents version confusion between team members.'],
                ['Adopt a token-based design system from day one', 'The client has plans to white-label the product — tokens make theming trivial later.'],
                ['Scope the MVP to read-only dashboard views, defer edit flows', 'Client feedback from discovery confirmed users mostly monitor, rarely modify. Reduces complexity by 40%.'],
                ['Use Recharts for all chart components', 'Lightweight, composable, and has good TypeScript support which the frontend team needs.'],
                ['Present two visual directions to client before locking style', 'Avoids late-stage design pivots that cost sprint time.'],
            ],
            'AI Onboarding Flow' => [
                ['Build onboarding as a standalone Vue wizard, not embedded in the main app shell', 'Allows faster iteration without touching core navigation or authentication flows.'],
                ['Default to progressive disclosure — one question per screen', 'User research from two sessions showed multi-question screens caused drop-off above 60%.'],
                ['Skip email verification step in the demo build', 'Adds friction with no payoff during the prototype phase; can be re-added before launch.'],
                ['Use Claude for personalized welcome messages based on role selection', 'Small AI touch-point that makes the experience feel tailored from step one.'],
            ],
            'Design System v2.0' => [
                ['Treat the component library as a product, not a project', 'Means versioning, changelog, deprecation notices, and consumer communication from day one.'],
                ['Target WCAG 2.1 AA as the minimum accessibility standard', 'Agreed with stakeholders — legal risk in some markets if below AA.'],
                ['Freeze v1 before beginning any v2 migration work', 'Teams still using v1 components need a stable base; parallel development caused drift in the previous cycle.'],
                ['Publish Storybook as the canonical documentation site', 'Replaces the Notion wiki that was always out of date. Storybook renders actual components.'],
                ['Limit v2 initial scope to 32 core components', 'Full audit identified 90+ v1 components; 32 cover 95% of real usage. The rest can follow in patch releases.'],
                ['Establish a component acceptance checklist before merge', 'Requires: design review, unit test, accessibility audit, Storybook story, and changelog entry.'],
            ],
            'Mobile App Prototype' => [
                ['Build in Figma first, validate with 5 users before any code', 'Prototype fidelity matches what Expo can actually build; avoids throwaway code.'],
                ['Skip backend integration for the prototype — use hardcoded JSON', 'Two-week timeline does not allow real API work; static data is sufficient for usability testing.'],
            ],
            'Brand Identity Refresh' => [
                ['Deliver three distinct visual directions, not variations of one idea', 'Client explicitly asked to be surprised. One direction is safe, one is bold, one is unexpected.'],
                ['Lock color palette and typography before beginning logo exploration', 'Prevents logo work from being undone when palette changes — happened in a prior project.'],
                ['Use variable fonts to reduce asset count', 'Single font file handles all weights, saving ~120 KB per page load.'],
                ['Include dark mode guidelines in the brand doc', 'Client operates a SaaS product — dark mode is expected by their users.'],
            ],
            default => [
                ['Default to async-first communication', 'Team spans multiple time zones — synchronous-only would block progress daily.'],
                ['Keep scope documented and version-controlled', 'Prevents scope creep and gives a reference point for tradeoff conversations.'],
            ],
        };
    }

    // ── Alive Signals ─────────────────────────────────────────────────────────

    private function seedAliveSignals(Project $project, $members): void
    {
        foreach ($members as $i => $member) {
            // Each member has 3–6 signals over the last 10 days
            $signals = 3 + ($i % 4);
            for ($s = 0; $s < $signals; $s++) {
                $daysAgo = $s * intval(10 / max($signals, 1));
                AliveSignal::firstOrCreate([
                    'project_id' => $project->id,
                    'user_id'    => $member->id,
                    'created_at' => Carbon::now()->subDays($daysAgo)->subHours($i),
                ]);
            }
        }
    }

    // ── Project Pulse ─────────────────────────────────────────────────────────

    private function seedProjectPulse(Project $project, string $status): void
    {
        if (ProjectPulseLog::where('project_id', $project->id)->exists()) return;

        $pulseStatus = match ($status) {
            Project::STATUS_ACTIVE    => 'resolved',
            Project::STATUS_COMPLETED => 'resolved',
            default                   => 'nudge_sent',
        };

        for ($i = 3; $i >= 0; $i--) {
            ProjectPulseLog::insert([
                'project_id'   => $project->id,
                'triggered_at' => Carbon::now()->subDays($i * 3),
                'signals'      => json_encode([
                    'alive_signals_last_48h' => 3 - $i,
                    'messages_last_48h'      => 5 + $i,
                    'tasks_updated_last_48h' => 2,
                    'last_commit_hours_ago'  => 12 + ($i * 6),
                ]),
                'status'       => $i === 0 ? $pulseStatus : 'resolved',
            ]);
        }
    }

    // ── Project Files ─────────────────────────────────────────────────────────

    private function seedFiles(Project $project, $members, string $title): void
    {
        if (ProjectFile::where('project_id', $project->id)->exists()) return;

        $files = $this->filesFor($title);
        foreach ($files as $i => $file) {
            $uploader = $members[$i % $members->count()];
            ProjectFile::create([
                'project_id'  => $project->id,
                'uploaded_by' => $uploader->id,
                'file_name'   => $file[0],
                'mime_type'   => $file[1],
                'file_size'   => $file[2],
                'file_url'    => 'demo/' . $project->id . '/' . $file[0],
            ]);
        }
    }

    private function filesFor(string $title): array
    {
        // [filename, mime_type, size_bytes]
        return match ($title) {
            'Redesign Fintech Dashboard' => [
                ['fintech-dashboard-discovery.pdf',   'application/pdf',     2_450_000],
                ['dashboard-wireframes-v3.fig',       'application/octet-stream', 8_200_000],
                ['component-specs-handoff.pdf',        'application/pdf',     1_100_000],
                ['client-feedback-round-2.docx',       'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 320_000],
                ['color-tokens-export.json',           'application/json',    14_000],
            ],
            'AI Onboarding Flow' => [
                ['onboarding-flow-prototype.fig',      'application/octet-stream', 5_600_000],
                ['user-research-notes.pdf',            'application/pdf',     890_000],
                ['claude-prompt-drafts.txt',           'text/plain',          18_000],
            ],
            'Design System v2.0' => [
                ['ds-v2-component-audit.xlsx',         'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 780_000],
                ['design-tokens-v2.json',              'application/json',    42_000],
                ['storybook-config-baseline.zip',      'application/zip',     1_900_000],
                ['accessibility-checklist.pdf',        'application/pdf',     540_000],
                ['changelog-draft.md',                 'text/markdown',       28_000],
                ['figma-component-export.fig',         'application/octet-stream', 12_400_000],
            ],
            'Mobile App Prototype' => [
                ['mobile-prototype-v1.fig',            'application/octet-stream', 6_100_000],
                ['usability-test-script.docx',         'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 210_000],
            ],
            'Brand Identity Refresh' => [
                ['brand-discovery-brief.pdf',          'application/pdf',     1_200_000],
                ['logo-explorations-round-1.fig',      'application/octet-stream', 9_800_000],
                ['color-palette-final.ase',            'application/octet-stream', 8_000],
                ['brand-guidelines-draft.pdf',         'application/pdf',     4_600_000],
                ['social-media-templates.zip',         'application/zip',     11_200_000],
            ],
            default => [
                ['project-brief.pdf', 'application/pdf', 500_000],
            ],
        };
    }

    // ── Ratings ───────────────────────────────────────────────────────────────

    private function seedRatings(Project $project, $members): void
    {
        if (Rating::where('project_id', $project->id)->exists()) return;

        $memberList = $members->values()->all();
        $count = count($memberList);

        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count; $j++) {
                if ($i === $j) continue;
                $rater = $memberList[$i];
                $rated = $memberList[$j];

                // Check if Rating has separate score columns or a single score column
                Rating::firstOrCreate(
                    [
                        'project_id' => $project->id,
                        'rater_id'   => $rater->id,
                        'rated_id'   => $rated->id,
                    ],
                    [
                        'communication_score' => rand(4, 5),
                        'reliability_score'   => rand(4, 5),
                        'contribution_score'  => rand(4, 5),
                        'overall_score'       => round(rand(40, 50) / 10, 2),
                        'comment'             => $this->randomRatingComment(),
                    ]
                );
            }
        }
    }

    private function randomRatingComment(): string
    {
        $comments = [
            'Consistently delivered high quality work and communicated blockers early.',
            'Great collaborative spirit — always willing to help others unblock.',
            'Brought clear thinking to ambiguous problems. Would work with again.',
            'Reliable and thorough. Never missed a commitment without flagging it first.',
            'Strong design instincts and good at defending decisions with evidence.',
            'Kept the team aligned during a chaotic sprint. Very appreciated.',
            'Excellent attention to detail in handoff files. Made dev work much smoother.',
            'Proactive about sharing context and keeping everyone in the loop.',
        ];
        return $comments[array_rand($comments)];
    }

    // ── Skill Endorsements ────────────────────────────────────────────────────

    private function seedEndorsements(Project $project, $members, string $title): void
    {
        if (SkillEndorsement::where('project_id', $project->id)->exists()) return;

        $skillMap = $this->skillMapFor($title);
        $memberList = $members->values()->all();

        foreach ($memberList as $endorser) {
            // Each member endorses 2–3 others for relevant skills
            $targets = collect($memberList)->filter(fn($m) => $m->id !== $endorser->id)->shuffle()->take(2);
            foreach ($targets as $endorsed) {
                $skills = $skillMap[array_rand($skillMap)];
                foreach ($skills as $skill) {
                    SkillEndorsement::firstOrCreate([
                        'project_id'  => $project->id,
                        'endorser_id' => $endorser->id,
                        'endorsed_id' => $endorsed->id,
                        'skill_name'  => $skill,
                    ]);
                    DB::table('user_skills')
                        ->where('user_id', $endorsed->id)
                        ->where('skill_name', $skill)
                        ->update(['is_endorsed' => true]);
                }
            }
        }
    }

    private function skillMapFor(string $title): array
    {
        return match ($title) {
            'Redesign Fintech Dashboard'  => [['Figma'], ['User Research'], ['Design Systems'], ['Prototyping'], ['Accessibility']],
            'AI Onboarding Flow'          => [['UX Writing'], ['Figma'], ['Prompt Design'], ['User Testing']],
            'Design System v2.0'          => [['Design Systems'], ['Figma'], ['Storybook'], ['Accessibility'], ['Documentation']],
            'Mobile App Prototype'        => [['Mobile UX'], ['Figma'], ['Usability Testing']],
            'Brand Identity Refresh'      => [['Visual Design'], ['Branding'], ['Typography'], ['Figma']],
            default                       => [['Collaboration'], ['Communication']],
        };
    }

    // ── Chemistry Score ───────────────────────────────────────────────────────

    private function seedChemistryScore(Project $project, $members): void
    {
        if (ChemistryScore::where('project_id', $project->id)->exists()) return;
        if ($members->isEmpty()) return;

        ChemistryScore::create([
            'project_id'   => $project->id,
            'triggered_by' => $members->first()->id,
            'score_data'   => json_encode([
                'label'     => 'Strong Fit',
                'summary'   => 'The team has well-aligned working styles with strong overlap in async communication preferences and feedback approach.',
                'alignment' => [
                    'All members prefer async-first communication',
                    'Shared preference for structured feedback',
                    'Consistent weekly availability (20–30 hours)',
                ],
                'friction'  => [
                    'Minor timezone difference between two members',
                ],
            ]),
        ]);
    }

    // ── AI Suggestions ────────────────────────────────────────────────────────

    private function seedAiSuggestions(Project $project, $members, User $sara, string $title): void
    {
        if (AiSuggestion::where('source_id', $project->id)->where('source_type', 'project')->exists()) return;

        $suggestions = $this->aiSuggestionsFor($title);

        AiSuggestion::create([
            'user_id'        => $sara->id,
            'source_type'    => 'project',
            'source_id'      => $project->id,
            'cv_text'        => $suggestions['cv'],
            'portfolio_text' => $suggestions['portfolio'],
            'linkedin_text'  => $suggestions['linkedin'],
        ]);
    }

    private function aiSuggestionsFor(string $title): array
    {
        return match ($title) {
            'Mobile App Prototype' => [
                'cv'        => 'Led UX design and prototyping for a cross-platform mobile application, conducting usability sessions and iterating on high-fidelity Figma prototypes based on direct user feedback.',
                'portfolio' => 'Designed and tested a mobile app prototype from zero to user-validated flows in two weeks. Ran five usability sessions that informed three major navigation changes before a single line of code was written.',
                'linkedin'  => "Just shipped a mobile prototype I'm really proud of. Two weeks, five user sessions, and a complete navigation overhaul — all before touching code. Proof that design validation upfront saves everyone time. #UXDesign #MobileDesign #Prototyping",
            ],
            'Brand Identity Refresh' => [
                'cv'        => 'Delivered a complete brand identity refresh including logo system, color palette, typography, and dark mode guidelines for a Lebanese SaaS product, working from discovery brief to launch-ready brand document.',
                'portfolio' => 'Refreshed a SaaS brand from the ground up — three distinct visual directions presented to the client, one chosen, then refined into a production-ready system with variable fonts, a dark mode guide, and social templates.',
                'linkedin'  => "Wrapped up a brand refresh project this week. We went from zero to a full brand system with variable fonts and dark mode guidelines in six weeks. The part I learned most from: showing three genuinely different directions instead of variations of one idea. Clients make much faster decisions when the options are actually distinct. #BrandDesign #VisualDesign",
            ],
            default => [
                'cv'        => 'Contributed as a core team member on a collaborative product design project, delivering design artifacts and collaborating cross-functionally to meet project milestones.',
                'portfolio' => 'Worked as part of a focused team to solve a real design problem from discovery to delivery, applying systems thinking and user-centered methods throughout.',
                'linkedin'  => 'Completed another great collaborative project on DevConnect. Building things with a real team — even on practice projects — is a completely different experience from working alone. #CollaborativeDesign #DevConnectLB',
            ],
        };
    }
}