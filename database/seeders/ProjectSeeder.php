<?php

namespace Database\Seeders;

use App\Models\AiSuggestion;
use App\Models\Application;
use App\Models\DecisionLog;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectRole;
use App\Models\Rating;
use App\Models\SkillEndorsement;
use App\Models\Task;
use App\Models\TeamAgreement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $rami  = User::where('email', 'rami@devconnect.lb')->first();
        $lara  = User::where('email', 'lara@devconnect.lb')->first();
        $tarek = User::where('email', 'tarek@devconnect.lb')->first();
        $maya  = User::where('email', 'maya@devconnect.lb')->first();
        $u6    = User::where('email', 'user6@devconnect.lb')->first();
        $u7    = User::where('email', 'user7@devconnect.lb')->first();
        $u8    = User::where('email', 'user8@devconnect.lb')->first();
        $u9    = User::where('email', 'user9@devconnect.lb')->first();
        $u10   = User::where('email', 'user10@devconnect.lb')->first();
        $u11   = User::where('email', 'user11@devconnect.lb')->first();
        $u12   = User::where('email', 'user12@devconnect.lb')->first();

        // =====================================================================
        // PROJECT 1 — DevTrack (COMPLETED, owned by Rami)
        // =====================================================================
        $p1 = Project::create([
            'owner_id'    => $rami->id,
            'title'       => 'DevTrack — Lebanese Developer Portfolio Builder',
            'description' => 'A developer portfolio builder designed for Lebanese developers to showcase their skills, projects, and contributions to the local tech community.',
            'status'      => 'completed',
            'type'        => 'practice',
            'domain'      => 'Web',
            'tech_stack'  => ['Laravel', 'Vue', 'MySQL', 'Tailwind'],
            'repo_url'    => 'https://github.com/ramihaddad/devtrack',
            'max_members' => 5,
            'completed_at' => now()->subDays(55),
        ]);

        // Members
        $this->addMember($p1->id, $rami->id,  'Backend Dev',  'active', now()->subDays(90));
        $this->addMember($p1->id, $lara->id,  'Frontend Dev', 'active', now()->subDays(85));
        $this->addMember($p1->id, $tarek->id, 'Frontend Dev', 'active', now()->subDays(80));
        $this->addMember($p1->id, $u6->id,    'Backend Dev',  'active', now()->subDays(80));

        // Roles (2 — both filled)
        ProjectRole::create(['project_id' => $p1->id, 'role_name' => 'Backend Dev',  'slots' => 2, 'filled' => 2, 'is_open' => false, 'description' => 'Laravel API development']);
        ProjectRole::create(['project_id' => $p1->id, 'role_name' => 'Frontend Dev', 'slots' => 2, 'filled' => 2, 'is_open' => false, 'description' => 'Vue 3 component work']);

        // Tasks (8 total: 6 done, 2 in_progress)
        $p1Members = [$rami, $lara, $tarek, $u6];
        $taskTitles = [
            'Set up Laravel project structure',
            'Implement GitHub OAuth login',
            'Design portfolio card component',
            'Build user profile API endpoints',
            'Create project listing page',
            'Add skill endorsement feature',
            'Integrate Tailwind design system',
            'Write API documentation',
        ];
        foreach ($taskTitles as $i => $title) {
            $status = $i < 6 ? 'done' : 'in_progress';
            Task::create([
                'project_id'   => $p1->id,
                'assigned_to'  => $p1Members[$i % 4]->id,
                'title'        => $title,
                'description'  => fake()->sentence(10),
                'energy'       => fake()->randomElement(['quick_win', 'deep_work']),
                'priority'     => fake()->randomElement(['medium', 'high']),
                'status'       => $status,
                'position'     => $i,
                'completed_at' => $status === 'done' ? now()->subDays(rand(10, 50)) : null,
            ]);
        }

        // Decision log (2 entries)
        DecisionLog::create(['project_id' => $p1->id, 'user_id' => $rami->id, 'decision' => 'Use Inertia.js instead of a separate SPA', 'reason' => 'Reduces complexity and keeps team aligned on a single stack.']);
        DecisionLog::create(['project_id' => $p1->id, 'user_id' => $lara->id, 'decision' => 'Switch from Livewire to Vue 3 for richer interactivity', 'reason' => 'Portfolio cards require complex client-side state.']);

        // Ratings — all 4 members rate each other (12 rows)
        $ratingMatrix = [
            [$rami,  $lara,  5, 4, 4],
            [$rami,  $tarek, 4, 5, 3],
            [$rami,  $u6,    4, 4, 5],
            [$lara,  $rami,  5, 4, 4],
            [$lara,  $tarek, 4, 4, 4],
            [$lara,  $u6,    5, 5, 3],
            [$tarek, $rami,  4, 5, 4],
            [$tarek, $lara,  5, 4, 5],
            [$tarek, $u6,    4, 4, 4],
            [$u6,    $rami,  4, 4, 5],
            [$u6,    $lara,  5, 5, 4],
            [$u6,    $tarek, 4, 5, 3],
        ];
        foreach ($ratingMatrix as [$rater, $rated, $c, $r, $contrib]) {
            Rating::create([
                'project_id'         => $p1->id,
                'rater_id'           => $rater->id,
                'rated_id'           => $rated->id,
                'communication_score' => $c,
                'reliability_score'  => $r,
                'contribution_score' => $contrib,
                'overall_score'      => round(($c + $r + $contrib) / 3, 2),
            ]);
        }

        // Skill endorsements (each member endorses 2 others × 2 skills = 16 rows)
        $endorsements = [
            [$rami,  $lara,  'Laravel'],  [$rami,  $lara,  'Vue'],
            [$rami,  $tarek, 'Figma'],    [$rami,  $tarek, 'Tailwind'],
            [$lara,  $rami,  'Vue'],      [$lara,  $rami,  'MySQL'],
            [$lara,  $u6,    'React'],    [$lara,  $u6,    'Node'],
            [$tarek, $rami,  'Laravel'],  [$tarek, $rami,  'TypeScript'],
            [$tarek, $lara,  'Vue'],      [$tarek, $lara,  'Tailwind'],
            [$u6,    $rami,  'Node'],     [$u6,    $rami,  'Docker'],
            [$u6,    $lara,  'MySQL'],    [$u6,    $lara,  'Redis'],
        ];
        foreach ($endorsements as [$endorser, $endorsed, $skill]) {
            SkillEndorsement::create([
                'project_id'  => $p1->id,
                'endorser_id' => $endorser->id,
                'endorsed_id' => $endorsed->id,
                'skill_name'  => $skill,
            ]);
            // Keep user_skills in sync
            DB::table('user_skills')
                ->where('user_id', $endorsed->id)
                ->where('skill_name', $skill)
                ->update(['is_endorsed' => true, 'endorsement_count' => DB::raw('endorsement_count + 1')]);
        }

        // AI suggestions (1 per member)
        $aiTexts = [
            $rami->id  => ['cv' => 'Led backend development for DevTrack, a Lebanese developer portfolio platform built with Laravel 12 and Vue 3. Delivered key API endpoints and mentored junior teammates throughout the project.', 'portfolio' => 'Built DevTrack using Laravel, Vue 3, and MySQL. Implemented GitHub OAuth, portfolio APIs, and a role-based team system for Lebanese developers.', 'linkedin' => 'Contributed as lead backend developer on DevTrack, a portfolio builder for the Lebanese developer community. Leveraged Laravel, Inertia.js, and MySQL to ship robust features.'],
            $lara->id  => ['cv' => 'Developed frontend components for DevTrack using Vue 3 and Tailwind CSS. Collaborated closely with the backend team to deliver a seamless Inertia.js user experience.', 'portfolio' => 'Designed and built the Vue 3 frontend for DevTrack, focusing on portfolio card components and responsive layouts with Tailwind CSS.', 'linkedin' => 'Served as frontend developer on DevTrack, a developer portfolio builder. Built reusable Vue 3 components and integrated with Laravel APIs via Inertia.js.'],
            $tarek->id => ['cv' => 'Designed the UI/UX for DevTrack, crafting Figma prototypes and implementing pixel-perfect Tailwind CSS components. Ensured design consistency across all portfolio views.', 'portfolio' => 'Created the complete UI system for DevTrack using Figma and Tailwind CSS. Defined the design language and component library used by the entire team.', 'linkedin' => 'UI/UX designer for DevTrack. Delivered end-to-end design from wireframes to production-ready Tailwind CSS components for a Lebanese developer platform.'],
            $u6->id    => ['cv' => 'Contributed backend features to DevTrack, implementing the skill endorsement API and database optimisations. Worked in a Laravel 12 environment with MySQL and Redis.', 'portfolio' => 'Implemented the skill endorsement and contribution tracking backend for DevTrack. Optimised MySQL queries to improve portfolio page load times significantly.', 'linkedin' => 'Backend contributor on DevTrack, an open-source Lebanese developer platform. Developed REST endpoints and implemented caching strategies using Redis.'],
        ];
        foreach ($aiTexts as $userId => $texts) {
            AiSuggestion::create([
                'user_id'        => $userId,
                'source_type'    => 'project',
                'source_id'      => $p1->id,
                'cv_text'        => $texts['cv'],
                'portfolio_text' => $texts['portfolio'],
                'linkedin_text'  => $texts['linkedin'],
                'model'          => 'claude-haiku-4-5-20251001',
                'tokens_used'    => rand(900, 1400),
            ]);
        }

        // Pulse log (3 entries, last one resolved)
        DB::table('project_pulse_log')->insert([
            ['project_id' => $p1->id, 'signals' => json_encode(['active_members' => 4, 'tasks_done' => 2, 'last_activity_days' => 10]), 'status' => 'nudge_sent', 'triggered_at' => now()->subDays(70)],
            ['project_id' => $p1->id, 'signals' => json_encode(['active_members' => 4, 'tasks_done' => 4, 'last_activity_days' => 4]),  'status' => 'nudge_sent', 'triggered_at' => now()->subDays(60)],
            ['project_id' => $p1->id, 'signals' => json_encode(['active_members' => 4, 'tasks_done' => 6, 'last_activity_days' => 1]),  'status' => 'resolved',   'triggered_at' => now()->subDays(55)],
        ]);

        // =====================================================================
        // PROJECT 2 — SouqCode (ACTIVE, owned by Rami)
        // =====================================================================
        $p2 = Project::create([
            'owner_id'    => $rami->id,
            'title'       => 'SouqCode — Lebanese Freelance Marketplace',
            'description' => 'A freelance marketplace connecting Lebanese developers with local clients, featuring real-time bidding, escrow payments, and project milestone tracking.',
            'status'      => 'active',
            'type'        => 'practice',
            'domain'      => 'Marketplace',
            'tech_stack'  => ['Laravel', 'React', 'MySQL', 'Redis'],
            'repo_url'    => 'https://github.com/ramihaddad/souqcode',
            'max_members' => 5,
        ]);

        $this->addMember($p2->id, $rami->id, 'Backend Dev', 'active', now()->subDays(40));
        $this->addMember($p2->id, $maya->id, 'PM',          'active', now()->subDays(38));
        $this->addMember($p2->id, $u7->id,   'Frontend Dev', 'active', now()->subDays(35));
        $this->addMember($p2->id, $u8->id,   'Backend Dev', 'active', now()->subDays(35));

        ProjectRole::create(['project_id' => $p2->id, 'role_name' => 'Backend Dev',  'slots' => 2, 'filled' => 2, 'is_open' => false, 'description' => 'Laravel and Redis API work']);
        ProjectRole::create(['project_id' => $p2->id, 'role_name' => 'Frontend Dev', 'slots' => 1, 'filled' => 1, 'is_open' => false, 'description' => 'React SPA components']);
        ProjectRole::create(['project_id' => $p2->id, 'role_name' => 'ML Engineer',  'slots' => 1, 'filled' => 0, 'is_open' => true,  'description' => 'Search ranking model']);

        $p2Tasks = [
            ['Set up React frontend scaffold', 'done',        $rami->id],
            ['Build freelancer profile API',   'done',        $u8->id],
            ['Implement bid submission flow',  'in_progress', $u7->id],
            ['Design escrow payment module',   'in_progress', $rami->id],
            ['Create admin dashboard mockup',  'in_progress', $maya->id],
            ['Write project brief template',   'todo',        $u8->id],
        ];
        foreach ($p2Tasks as $i => [$title, $status, $assignee]) {
            Task::create([
                'project_id'   => $p2->id,
                'assigned_to'  => $assignee,
                'title'        => $title,
                'description'  => fake()->sentence(8),
                'energy'       => fake()->randomElement(['quick_win', 'deep_work']),
                'priority'     => 'medium',
                'status'       => $status,
                'position'     => $i,
                'completed_at' => $status === 'done' ? now()->subDays(rand(5, 20)) : null,
            ]);
        }

        DecisionLog::create(['project_id' => $p2->id, 'user_id' => $rami->id, 'decision' => 'Use React instead of Vue for the frontend', 'reason' => 'Larger candidate pool and existing team expertise.']);

        DB::table('project_pulse_log')->insert([
            ['project_id' => $p2->id, 'signals' => json_encode(['active_members' => 4, 'tasks_done' => 2, 'last_activity_days' => 2]), 'status' => 'resolved', 'triggered_at' => now()->subDays(5)],
        ]);

        // =====================================================================
        // PROJECT 3 — MapLB (AT RISK, owned by Lara)
        // =====================================================================
        $p3 = Project::create([
            'owner_id'    => $lara->id,
            'title'       => 'MapLB — Interactive Lebanon Tech Map',
            'description' => 'An interactive visualisation of the Lebanese tech ecosystem, showing active developers, companies, and coding communities across the country.',
            'status'      => 'at_risk',
            'type'        => 'practice',
            'domain'      => 'Data',
            'tech_stack'  => ['Vue', 'Python', 'MySQL'],
            'repo_url'    => null,
            'max_members' => 4,
        ]);

        $this->addMember($p3->id, $lara->id, 'Full Stack', 'active', now()->subDays(50));
        $this->addMember($p3->id, $u9->id,   'Backend Dev', 'active', now()->subDays(48));
        $this->addMember($p3->id, $u10->id,  'Designer',   'active', now()->subDays(46));

        ProjectRole::create(['project_id' => $p3->id, 'role_name' => 'Data Engineer', 'slots' => 1, 'filled' => 1, 'is_open' => false, 'description' => 'Python data pipeline']);
        ProjectRole::create(['project_id' => $p3->id, 'role_name' => 'Frontend Dev',  'slots' => 1, 'filled' => 0, 'is_open' => true,  'description' => 'Vue 3 map interface']);

        $p3Tasks = [
            ['Scrape developer directory data',     'in_progress', 'blocked',  $u9->id],
            ['Build Python ETL pipeline',           'in_progress', 'blocked',  $u9->id],
            ['Design map marker components in Vue', 'todo',        'deep_work', $lara->id],
            ['Set up MySQL geo-spatial queries',    'todo',        'deep_work', $u10->id],
        ];
        foreach ($p3Tasks as $i => [$title, $status, $energy, $assignee]) {
            Task::create([
                'project_id'  => $p3->id,
                'assigned_to' => $assignee,
                'title'       => $title,
                'description' => fake()->sentence(8),
                'energy'      => $energy,
                'priority'    => 'high',
                'status'      => $status,
                'position'    => $i,
            ]);
        }

        DB::table('project_pulse_log')->insert([
            ['project_id' => $p3->id, 'signals' => json_encode(['active_members' => 1, 'tasks_done' => 0, 'last_activity_days' => 18, 'missing_repo' => true]), 'status' => 'at_risk', 'triggered_at' => now()->subDays(3)],
        ]);

        // =====================================================================
        // PROJECT 4 — ZaytounAI (OPEN, owned by Maya)
        // =====================================================================
        $p4 = Project::create([
            'owner_id'    => $maya->id,
            'title'       => 'ZaytounAI — Arabic Recipe AI Assistant',
            'description' => 'An AI-powered assistant specialising in Lebanese and Arabic cuisine, providing step-by-step recipe guidance, ingredient substitutions, and meal planning.',
            'status'      => 'open',
            'type'        => 'practice',
            'domain'      => 'AI',
            'tech_stack'  => ['Python', 'Vue', 'Laravel'],
            'repo_url'    => 'https://github.com/user5/zaytounai',
            'max_members' => 4,
        ]);

        $this->addMember($p4->id, $maya->id, 'PM', 'active', now()->subDays(10));

        $backendRole  = ProjectRole::create(['project_id' => $p4->id, 'role_name' => 'Backend Dev',  'slots' => 1, 'filled' => 0, 'is_open' => true, 'description' => 'Laravel + Python AI integration']);
        $mlRole       = ProjectRole::create(['project_id' => $p4->id, 'role_name' => 'ML Engineer',  'slots' => 1, 'filled' => 0, 'is_open' => true, 'description' => 'LLM fine-tuning and prompt engineering']);
        $frontendRole = ProjectRole::create(['project_id' => $p4->id, 'role_name' => 'Frontend Dev', 'slots' => 1, 'filled' => 0, 'is_open' => true, 'description' => 'Vue 3 chat interface']);

        Task::create([
            'project_id'  => $p4->id,
            'assigned_to' => $maya->id,
            'title'       => 'Define AI recipe assistant requirements and tech stack',
            'description' => 'Document the features, API design, and model selection for the Arabic recipe AI.',
            'energy'      => 'deep_work',
            'priority'    => 'high',
            'status'      => 'in_progress',
            'position'    => 0,
        ]);

        // Applications from User6 and User7
        Application::create([
            'project_id' => $p4->id,
            'user_id'    => $u6->id,
            'role_id'    => $backendRole->id,
            'message'    => 'I have strong Laravel experience and would love to help integrate the AI backend for this project.',
            'status'     => 'pending',
        ]);
        Application::create([
            'project_id' => $p4->id,
            'user_id'    => $u7->id,
            'role_id'    => $mlRole->id,
            'message'    => 'I have been studying LLM fine-tuning and would love to work on the Arabic recipe model.',
            'status'     => 'pending',
        ]);

        // =====================================================================
        // PROJECT 5 — NajahEd (REAL CLIENT, owned by Rami)
        // =====================================================================
        $p5 = Project::create([
            'owner_id'    => $rami->id,
            'title'       => 'NajahEd — LMS for Lebanese Schools',
            'description' => 'A full-featured learning management system tailored for Lebanese schools, supporting Arabic content, student progress tracking, teacher dashboards, and parent notifications.',
            'status'      => 'active',
            'type'        => 'real_client',
            'domain'      => 'EdTech',
            'tech_stack'  => ['Laravel', 'Vue', 'MySQL', 'Docker'],
            'repo_url'    => 'https://github.com/ramihaddad/najahed',
            'max_members' => 6,
        ]);

        $p5Members = [
            [$rami->id,  'Backend Dev',  now()->subDays(30)],
            [$lara->id,  'Frontend Dev', now()->subDays(28)],
            [$tarek->id, 'Designer',     now()->subDays(27)],
            [$u11->id,   'PM',           now()->subDays(26)],
            [$u12->id,   'DevOps',       now()->subDays(25)],
        ];
        foreach ($p5Members as [$uid, $role, $joinedAt]) {
            $this->addMember($p5->id, $uid, $role, 'active', $joinedAt);
        }

        ProjectRole::create(['project_id' => $p5->id, 'role_name' => 'Backend Dev',  'slots' => 1, 'filled' => 1, 'is_open' => false, 'description' => 'Laravel API and LMS core']);
        ProjectRole::create(['project_id' => $p5->id, 'role_name' => 'Frontend Dev', 'slots' => 1, 'filled' => 1, 'is_open' => false, 'description' => 'Vue 3 student/teacher UI']);
        ProjectRole::create(['project_id' => $p5->id, 'role_name' => 'DevOps',       'slots' => 1, 'filled' => 1, 'is_open' => false, 'description' => 'Docker deployment and CI/CD']);

        $p5Tasks = [
            ['Set up Docker compose environment',       'done',        $u12->id],
            ['Implement student enrolment API',         'done',        $rami->id],
            ['Build course content player in Vue',      'in_progress', $lara->id],
            ['Design teacher grade-book interface',     'in_progress', $tarek->id],
            ['Configure CI/CD pipeline for deployment', 'todo',        $u12->id],
        ];
        foreach ($p5Tasks as $i => [$title, $status, $assignee]) {
            Task::create([
                'project_id'   => $p5->id,
                'assigned_to'  => $assignee,
                'title'        => $title,
                'description'  => fake()->sentence(8),
                'energy'       => 'deep_work',
                'priority'     => 'high',
                'status'       => $status,
                'position'     => $i,
                'completed_at' => $status === 'done' ? now()->subDays(rand(5, 15)) : null,
            ]);
        }

        // Team agreements (NDA per member)
        $ndaText = "This Non-Disclosure Agreement governs the confidentiality of client information shared during the NajahEd project. All team members agree not to disclose proprietary content, student data, or business logic to third parties.";
        foreach ($p5Members as [$uid, , $joinedAt]) {
            TeamAgreement::create([
                'project_id'    => $p5->id,
                'user_id'       => $uid,
                'type'          => 'nda',
                'document_text' => $ndaText,
                'signed_at'     => $joinedAt,
                'ip_address'    => '127.0.0.1',
            ]);
        }
    }

    private function addMember(int $projectId, int $userId, string $role, string $status, $joinedAt): void
    {
        ProjectMember::create([
            'project_id'   => $projectId,
            'user_id'      => $userId,
            'role'         => $role,
            'status'       => $status,
            'access_level' => 1,
            'joined_at'    => $joinedAt,
        ]);
    }
}
