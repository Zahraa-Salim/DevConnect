<?php

namespace Database\Seeders;

use App\Models\HelpRequest;
use App\Models\HelpRequestComment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds the community peer-help board (routes/pages: /help-requests).
 *
 * The word "Help" in the UI should read "Ask the Community" or "Peer Q&A".
 * This seeder creates:
 *  - 12 open community questions across different tech tags
 *  - Several in-progress (claimed) questions
 *  - 3 resolved questions (showing full answer flow)
 *  - 2 questions from Sara (as requester)
 *  - 1 question to Sara (as mentor/expert)
 *  - Realistic comment threads on each
 *
 * Run after DemoUserProfileSeeder and DemoMentorSeeder.
 */
class DemoPeerHelpSeeder extends Seeder
{
    public function run(): void
    {
        $sara      = User::where('email', DemoUserProfileSeeder::SARA_EMAIL)->firstOrFail();
        $teammates = User::whereIn('email', array_column(DemoUserProfileSeeder::TEAMMATES, 'email'))
            ->orderBy('id')->get()->values();

        $community = User::where('email', 'like', 'demo.member%')
            ->orderBy('id')->limit(20)->get();

        $ahmad = $teammates->firstWhere('email', 'ahmad.nasser@demo.io');
        $lara  = $teammates->firstWhere('email', 'lara.haddad@demo.io');
        $omar  = $teammates->firstWhere('email', 'omar.fayad@demo.io');
        $maya  = $teammates->firstWhere('email', 'maya.rizk@demo.io');

        // ── Open questions ────────────────────────────────────────────────────

        $this->makeRequest(
            poster: $community[0],
            title:  'What is the best way to handle form validation in Vue 3 with Inertia?',
            desc:   'I am building a multi-step form with Inertia.js and Vue 3. I am not sure whether to use the Inertia useForm composable for all validation or handle validation client-side first then send to Laravel. The docs show both approaches. Which pattern scales better for complex forms with conditional fields?',
            tags:   ['Vue', 'Laravel', 'Inertia'],
            status: HelpRequest::STATUS_PENDING,
            comments: [
                [$ahmad, 'For most cases the Inertia useForm helper is the right default — it keeps validation server-authoritative and avoids duplicating rules. For forms with many conditional fields though, a hybrid approach works well: use Vuelidate for immediate client-side feedback (UX) and still submit through useForm to get the server errors into form.errors.'],
                [$lara, 'Agree with Ahmad. One thing to add: if your fields depend on each other (e.g. field B is only required when field A is "yes"), keep that logic in a computed property that feeds into a visibility ref, not in the validation rule itself. Makes it much easier to test.'],
                [$community[1], 'This was exactly my question last week. Ended up going server-only via Inertia and it was fine for our 6-step form. The flash of validation errors is fast enough on a local server.'],
            ]
        );

        $this->makeRequest(
            poster: $community[2],
            title:  'How do I properly type Inertia page props in TypeScript?',
            desc:   'I am trying to type the global shared props (auth.user, flash, notifications) that HandleInertiaRequests injects. I know about usePage() but I cannot get TypeScript to stop showing "any" for the auth object. Do I need to augment the InertiaApp types?',
            tags:   ['TypeScript', 'Inertia', 'Vue'],
            status: HelpRequest::STATUS_PENDING,
            comments: [
                [$ahmad, "Yes — you need to augment the PageProps interface. Create a file at resources/js/types/inertia.d.ts and add:\n\nimport { PageProps as InertiaPageProps } from '@inertiajs/core'\ndeclare module '@inertiajs/core' {\n  interface PageProps extends InertiaPageProps {\n    auth: { user: App.User | null }\n    flash: { success: string | null; error: string | null }\n  }\n}\n\nThen usePage().props.auth.user will be fully typed everywhere."],
            ]
        );

        $this->makeRequest(
            poster: $community[3],
            title:  'Laravel queue jobs are failing silently — how do I debug them?',
            desc:   'I have a job that sends a Slack notification. It runs fine when I call it directly but when dispatched to the queue it fails without any error in the logs. I have tried php artisan queue:listen but nothing appears. My QUEUE_CONNECTION is set to database.',
            tags:   ['Laravel', 'Queues', 'Debugging'],
            status: HelpRequest::STATUS_PENDING,
            comments: [
                [$omar, 'First check your failed_jobs table — run `php artisan queue:failed` and look for your job class name. Silent failures almost always mean an exception is thrown but not logged because the queue worker is not outputting verbose mode. Run `php artisan queue:listen --tries=1` to get full output. Also make sure your job implements ShouldQueue correctly and that the handle() method does not swallow exceptions.'],
                [$community[4], 'Had the same issue. In my case it was that the Slack webhook URL was stored as a config value but config() was being called before the app was fully booted inside the job constructor. Moving the call to handle() fixed it.'],
            ]
        );

        $this->makeRequest(
            poster: $community[5],
            title:  'When should I use Tailwind @apply vs utility classes directly?',
            desc:   'I keep going back and forth. My team uses both approaches and now the codebase is inconsistent. When is @apply actually the right tool and when should I just use utilities in the markup?',
            tags:   ['Tailwind', 'CSS'],
            status: HelpRequest::STATUS_PENDING,
            comments: [
                [$lara, 'Simple rule: use @apply only when you are creating a reusable class that maps to a specific design pattern (like a .btn-primary or .card) and that pattern appears in many places. Never use it to avoid long class strings in a one-off component — just keep the utilities in the markup. The Tailwind team themselves say: if you are not building a component library, prefer utilities.'],
                [$maya, 'Agreed. In Vue specifically, the right layer for encapsulation is the component, not @apply. If the same styles appear in five places, make a component. @apply in a global CSS file is a last resort.'],
            ]
        );

        $this->makeRequest(
            poster: $community[6],
            title:  'MySQL vs PostgreSQL for a new Laravel project — which should I choose?',
            desc:   'Starting a new project that will handle financial transactions, user profiles, and some JSON data. I have only used MySQL before. Is it worth switching to PostgreSQL? What specific features would I actually use?',
            tags:   ['MySQL', 'PostgreSQL', 'Laravel'],
            status: HelpRequest::STATUS_PENDING,
            comments: [
                [$ahmad, 'For financial transactions: PostgreSQL wins because of better transaction isolation levels (especially REPEATABLE READ and SERIALIZABLE), better handling of concurrent writes, and native support for ranges and arrays which are useful for financial date ranges. If your team already knows MySQL and you are under time pressure, MySQL 8 is fine — just enable strict mode and use InnoDB.'],
            ]
        );

        $this->makeRequest(
            poster: $community[7],
            title:  'How do I implement optimistic UI updates with Inertia.js?',
            desc:   'I want to update the UI immediately when a user clicks a button (e.g. toggling a like) without waiting for the server response. With a separate API I would update state and revert on error. How do I do this with Inertia where the server drives the page state?',
            tags:   ['Inertia', 'Vue', 'UX'],
            status: HelpRequest::STATUS_PENDING,
            comments: [
                [$lara, "Inertia doesn't block you from having local state. The pattern is: keep the toggle state in a ref(), update it immediately on click, then call router.patch() with preserveState: true. On success, props will update to confirm. On error, revert the ref. Since the visit is background you get the optimistic feel without leaving Inertia's model."],
            ]
        );

        $this->makeRequest(
            poster: $community[8],
            title:  'Is it worth learning Docker as a junior developer in Lebanon?',
            desc:   'I see Docker on most job postings but many local companies still deploy to shared hosting. Should I prioritize learning Docker or focus on getting stronger at Laravel and Vue first?',
            tags:   ['Docker', 'Career', 'DevOps'],
            status: HelpRequest::STATUS_PENDING,
            comments: [
                [$omar, 'Yes, but strategically. You do not need to master Docker Swarm or Kubernetes as a junior. Learn enough to run your development environment in Docker Compose so your setup matches production, and know how to build a simple Dockerfile for a Laravel app. That alone puts you ahead of most juniors in Lebanon right now. The investment is maybe 2–3 weekends.'],
                [$community[9], 'Plus it makes your own projects feel more serious — running php artisan in a container vs php in PATH is a real skill signal on a portfolio.'],
            ]
        );

        $this->makeRequest(
            poster: $community[10],
            title:  'How do I handle file uploads in Laravel that need to be processed asynchronously?',
            desc:   'Users upload CSV files up to 50 MB. Processing them synchronously times out. I know I should use queued jobs but I am not sure how to track the processing status and show a progress indicator to the user.',
            tags:   ['Laravel', 'Queues', 'File Uploads'],
            status: HelpRequest::STATUS_PENDING,
            comments: [
                [$ahmad, "Pattern that works well: 1) Store the file immediately on upload, return a job ID. 2) Dispatch a queued job that processes the CSV in chunks using LazyCollection so it doesn't blow memory. 3) Write progress to a cache key like 'import:{jobId}:progress'. 4) On the frontend, poll GET /import/{id}/status every 2 seconds. 5) Job completion dispatches a Reverb event to stop the polling. Simple and reliable."],
            ]
        );

        // ── In-progress (claimed) questions ───────────────────────────────────

        $this->makeRequest(
            poster: $community[11],
            title:  'Redis cache invalidation — when should I use tags vs manual key deletion?',
            desc:   'I have a multi-tenant Laravel app where tenants share some cached data but also have tenant-specific caches. I am finding it hard to know when to invalidate. Should I use cache tags or just delete by key pattern?',
            tags:   ['Redis', 'Laravel', 'Caching'],
            status: HelpRequest::STATUS_ACCEPTED,
            mentorId: $sara->id,
            comments: [
                [$sara, "Cache tags are the cleaner abstraction when you have logical groups of cached items — use them for things like all cached data for a given tenant (tag: 'tenant:{id}'). Key-pattern deletion (using SCAN or a naming convention) is more flexible for cross-cutting invalidation. For your case: tag by tenant, and within each tenant tag by data type. Then Cache::tags(['tenant:5', 'products'])->flush() when products change for tenant 5 — clear and explicit."],
                [$community[11], 'That makes a lot of sense. So I could have tags like tenant:5 and data:products, and flush just the intersection?'],
                [$sara, 'Exactly. In Laravel cache tags are OR — fluishing by tag clears all entries with ANY of those tags, not the intersection. So tag precisely and keep tags narrow. One tag per tenant, one per data type, flush tenant tag on full tenant data clear.'],
            ]
        );

        $this->makeRequest(
            poster: $community[12],
            title:  'My Figma auto-layout breaks in edge cases — how do I debug it?',
            desc:   'I have a card component using auto-layout. When text wraps to a second line the card height grows correctly but an icon at the bottom right does not stay pinned — it floats up. I have tried fixed positioning and absolute within the frame but the behavior is inconsistent.',
            tags:   ['Figma', 'Design', 'UI Components'],
            status: HelpRequest::STATUS_ACCEPTED,
            mentorId: $sara->id,
            comments: [
                [$sara, "This is a common auto-layout trap. The issue is usually that your icon frame is set to 'hug contents' vertically instead of 'fill container'. In an auto-layout frame, children that hug will not stretch to fill the parent — they sit at the top. Set the icon wrapper to 'fill container' height and use 'end' alignment. If you want the icon pinned to the bottom-right within a fixed space, use a nested frame with space-between as the content justification."],
            ]
        );

        // ── Sara asks the community (showing Sara as requester) ───────────────

        $this->makeRequest(
            poster: $sara,
            title:  'Best approach to version design tokens across multiple product teams?',
            desc:   'We have three product teams all consuming the same design system. When I update a token value (e.g. the primary color), two teams can take it immediately but one team has a hardcoded dependency on the old value in a third-party library. I need a way to version tokens so teams can opt in to breaking changes on their own schedule. Has anyone solved this in a way that does not require duplicating the token file?',
            tags:   ['Design Systems', 'Design Tokens', 'Figma'],
            status: HelpRequest::STATUS_PENDING,
            comments: [
                [$lara, "Style Dictionary supports multi-platform, multi-theme output from a single source. You can define a 'base' tier and 'alias' tiers separately, then version the alias file while keeping base stable. Teams consuming only base tokens won't be affected. Worth exploring if you are not already using Style Dictionary."],
                [$community[13], "We solved a similar problem with semantic versioning on the NPM package that exports our tokens. Major bump = breaking token change. Teams pin their major version and upgrade intentionally. The cost is more dependency management, but it made inter-team coordination much smoother."],
            ]
        );

        $this->makeRequest(
            poster: $sara,
            title:  'How do you document component behavior decisions for future team members?',
            desc:   'I am writing up our design system documentation and I realize we have made a lot of non-obvious decisions — why certain things are the way they are, what alternatives we rejected, etc. Where do you put this context? In Storybook? A separate doc site? Alongside the component in the file?',
            tags:   ['Design Systems', 'Documentation', 'Figma'],
            status: HelpRequest::STATUS_PENDING,
            comments: [
                [$lara, "I use an ADR (Architecture Decision Record) format — each component or pattern has an ADR file in the repo that documents: the decision, the context, the alternatives considered, and the rationale. Storybook for the interactive spec, ADR for the 'why' context. Newcomers read the ADR to understand intent."],
                [$maya, "We put it in a 'Design Decisions' annotation layer directly inside the Figma component — visible in dev mode but not in design view. Keeps it close to the source but invisible to stakeholders who don't need it."],
                [$omar, "Decision log per component in Notion, linked from Storybook's docs page. The link means it is always one click away from the component spec. Not perfect but it works because everyone already uses Notion."],
            ]
        );

        // ── Resolved questions (full answer flow) ─────────────────────────────

        $this->makeRequest(
            poster: $community[14],
            title:  'What is the difference between N+1 queries and lazy loading, and when should I eager load?',
            desc:   'I am reading about eager loading in Laravel and I understand the concept but I am not sure when to apply it. My projects work fine but I want to understand when I would actually see a performance problem.',
            tags:   ['Laravel', 'Eloquent', 'Performance'],
            status: HelpRequest::STATUS_RESPONDED,
            mentorId: $ahmad->id,
            comments: [
                [$ahmad, 'N+1 happens when you loop over a collection and access a relationship inside the loop. Example: User::all() returns 100 users. Accessing $user->posts inside a loop fires 100 separate queries (1 for the users + 100 for posts = 101 total). Eager loading: User::with(\'posts\')->get() fires 2 queries total regardless of how many users there are. In development you will not notice it until you have real data. Install Laravel Debugbar and you will see the query count jump from 2 to 100+ on any page with relationships. Moral: always eager load in controllers when you know you will access relationships in the view.'],
                [$community[14], 'That makes sense. So the rule is: if I know I will access a relationship in a loop or view, eager load it in the query?'],
                [$ahmad, 'Exactly. And use `with()` not just in controllers — also in API resources and when building Inertia props. The Inertia page will trigger lazy loading if you return an Eloquent model directly.'],
                [$community[14], 'Installed Debugbar and found 47 N+1 queries on my dashboard page. Fixed in an hour. Thank you!'],
            ]
        );

        $this->makeRequest(
            poster: $community[15],
            title:  'How do I add smooth scroll animations to sections when they enter the viewport?',
            desc:   'I want to add fade-in animations when page sections scroll into view. I see options like GSAP, AOS, Intersection Observer, and CSS animations. Which approach is best for a Vue 3 project that cares about performance?',
            tags:   ['Vue', 'CSS', 'Animation'],
            status: HelpRequest::STATUS_RESPONDED,
            mentorId: $lara->id,
            comments: [
                [$lara, "For Vue 3: use the Intersection Observer API directly. Create a composable useReveal() that returns a ref you attach to the element and a class you apply when isVisible is true. CSS transitions handle the animation — no library needed. Example:\n\nconst useReveal = (threshold = 0.1) => {\n  const el = ref(null)\n  const isVisible = ref(false)\n  onMounted(() => {\n    const io = new IntersectionObserver(([e]) => {\n      if (e.isIntersecting) { isVisible.value = true; io.disconnect() }\n    }, { threshold })\n    if (el.value) io.observe(el.value)\n  })\n  return { el, isVisible }\n}\n\nThen in your component: :class=\"{ 'opacity-0': !isVisible, 'translate-y-4': !isVisible }\" with a transition class. No dependency, no JavaScript animation loop, respects prefers-reduced-motion."],
                [$community[15], 'This is exactly what I needed. Implemented it as a directive instead of a composable so I can just add v-reveal to any element. Works perfectly.'],
            ]
        );
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function makeRequest(
        User   $poster,
        string $title,
        string $desc,
        array  $tags,
        string $status,
        ?int   $mentorId = null,
        array  $comments = [],
    ): void {
        $existing = HelpRequest::where('title', $title)->first();
        if ($existing) {
            // Add any missing comments
            foreach ($comments as [$author, $body]) {
                HelpRequestComment::firstOrCreate(
                    ['help_request_id' => $existing->id, 'user_id' => $author->id],
                    ['body' => $body]
                );
            }
            return;
        }

        $data = [
            'user_id'      => $poster->id,
            'requester_id' => $poster->id,
            'project_id'   => null,
            'title'        => $title,
            'description'  => $desc,
            'tech_tags'    => $tags,
            'status'       => $status,
            'context'      => $desc,
            'stack'        => implode(', ', $tags),
            'response'     => $status === HelpRequest::STATUS_RESPONDED
                ? ($comments ? end($comments)[1] : null)
                : null,
            'responded_at' => $status === HelpRequest::STATUS_RESPONDED ? Carbon::now()->subDays(rand(1, 10)) : null,
            'created_at'   => Carbon::now()->subDays(rand(1, 30)),
            'updated_at'   => Carbon::now()->subDays(rand(0, 5)),
        ];

        if ($mentorId !== null) {
            $data['mentor_id'] = $mentorId;
        }

        $request = HelpRequest::create($data);

        foreach ($comments as $i => [$author, $body]) {
            HelpRequestComment::create([
                'help_request_id' => $request->id,
                'user_id'         => $author->id,
                'body'            => $body,
                'created_at'      => $request->created_at->addHours($i + 1),
                'updated_at'      => $request->created_at->addHours($i + 1),
            ]);
        }
    }
}