<?php

namespace Database\Seeders;

use App\Models\ContributionLog;
use App\Models\GithubIssue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds open GitHub issues for the Contribute page (so it's never empty)
 * and contribution logs for Sara and her teammates (merged PRs showing
 * on the profile page and reputation score).
 *
 * Run after DemoUserProfileSeeder.
 */
class DemoContributionSeeder extends Seeder
{
    /** Open issues surfaced on the /contribute page for AI matching */
    private const OPEN_ISSUES = [
        // [repo_full_name, title, body, language, difficulty, labels]
        ['laravel/framework', 'Improve error message when route parameter regex fails', 'Currently the exception message does not include the parameter name or the regex pattern that failed, making debugging harder for new developers.', 'PHP', 'beginner', ['good first issue', 'bug']],
        ['laravel/framework', 'Add `whereJsonLength` support to SQLite driver', 'The `whereJsonLength` query builder method works on MySQL and PostgreSQL but is silently ignored on SQLite. Should either implement it or throw an informative exception.', 'PHP', 'intermediate', ['enhancement', 'help wanted']],
        ['inertiajs/inertia', 'Support for `defineOptions` in Vue 3 page components', 'When using defineOptions inside Inertia page components the layout resolution sometimes ignores the option. This is a regression from 1.0.', 'TypeScript', 'beginner', ['bug', 'good first issue']],
        ['vuejs/core', 'Improve type narrowing for v-model on custom components', 'TypeScript cannot infer the correct type when v-model is used on a component that accepts a union type. The inferred type collapses to never in strict mode.', 'TypeScript', 'intermediate', ['typescript', 'enhancement']],
        ['tailwindlabs/tailwindcss', 'Document @layer behavior with Vite HMR in development', 'When using @layer utilities inside component files the order of CSS injection differs between hot-reload and production build leading to specificity surprises.', 'CSS', 'beginner', ['documentation', 'good first issue']],
        ['tailwindlabs/tailwindcss', 'Add `text-wrap: pretty` as a utility class', 'CSS Text Level 4 adds text-wrap: pretty which prevents orphan words on the last line. Browsers supporting it: Chrome 117+, Firefox 121+.', 'JavaScript', 'beginner', ['enhancement', 'good first issue']],
        ['vitejs/vite', 'HMR breaks when tsconfig paths include wildcards', 'Using paths: { "@/*": ["src/*"] } in tsconfig causes HMR to stop working after the first code change. Full reload is required.', 'TypeScript', 'advanced', ['bug', 'hmr']],
        ['axios/axios', 'AbortController signal not respected on retry', 'When using axios-retry with an AbortController signal the retry fires even after the signal is aborted. Expected: no retry if signal is aborted.', 'JavaScript', 'intermediate', ['bug', 'help wanted']],
        ['prisma/prisma', 'Support for PostgreSQL `SKIP LOCKED` in select queries', 'PostgreSQL supports SELECT ... FOR UPDATE SKIP LOCKED for queue-like patterns. This is useful for job processing and cannot currently be expressed in Prisma without raw SQL.', 'TypeScript', 'advanced', ['enhancement', 'database']],
        ['redis/node-redis', 'TypeScript: improve type inference for pipeline commands', 'When using pipeline() the return type of exec() is (RedisCommandRawReply | Error)[] which loses per-command type information. Should infer a tuple type.', 'TypeScript', 'intermediate', ['typescript', 'enhancement']],
        ['mysql2/node-mysql2', 'Add connection timeout option to pool configuration', 'The pool configuration does not expose a timeout for acquiring a connection from the pool. When the pool is exhausted the client hangs indefinitely.', 'JavaScript', 'beginner', ['enhancement', 'good first issue']],
        ['docker/compose', 'Service dependency wait does not respect custom health check intervals', 'When a service uses depends_on with condition: service_healthy and the target service defines a health check with a non-default interval, compose polls too fast before the first check can complete.', 'Go', 'intermediate', ['bug', 'help wanted']],
        ['redis/redis-py', 'Add type stubs for pipeline return types', 'The pipeline() context manager returns an untyped list from execute(). Adding overloaded type stubs would enable mypy to infer per-command return types the same way node-redis could.', 'Python', 'beginner', ['good first issue', 'typing']],
        ['pallets/flask', 'Document streaming responses with async generators', 'The docs cover synchronous generators for streaming but async generators (required for async Flask routes) are not documented. Should add an example.', 'Python', 'beginner', ['documentation', 'good first issue']],
        ['psf/requests', 'Session.merge_environment_settings does not merge verify setting correctly', 'When REQUESTS_CA_BUNDLE is set and verify=False is passed explicitly to a request the environment CA bundle overrides the explicit False. Should prefer the explicit parameter.', 'Python', 'intermediate', ['bug']],
        ['denoland/deno', 'Improve error messages for import map resolution failures', 'When an import map specifier does not match any entry the error is generic. Should include which specifiers were tried and suggest similar entries from the map.', 'Rust', 'beginner', ['good first issue', 'dx']],
        ['vercel/next.js', 'App Router: `revalidateTag` should accept an array of tags', 'Currently `revalidateTag` only accepts a single string. Accepting a string[] would allow invalidating multiple cache tags in one call without multiple await calls.', 'TypeScript', 'beginner', ['enhancement', 'good first issue']],
        ['supabase/supabase', 'RLS policy editor should warn on missing `auth.uid()` reference', 'It is easy to write a policy that allows all rows because auth.uid() was omitted. A lint warning in the policy editor would prevent data leaks from misconfigured RLS.', 'TypeScript', 'intermediate', ['ux', 'security']],
        ['ant-design/ant-design', 'Table component: empty state should be configurable via slot', 'The Table empty state is currently a fixed string. Should support a render prop or slot so custom empty states can include illustrations or CTAs.', 'TypeScript', 'beginner', ['enhancement', 'good first issue']],
        ['chakra-ui/chakra-ui', 'Form label association breaks when FormControl is inside a portal', 'When FormControl is rendered inside a Portal the htmlFor attribute on FormLabel does not resolve to the input because it is in a different DOM subtree.', 'TypeScript', 'intermediate', ['bug', 'accessibility']],
        // Extra open issues to fill the page
        ['expo/expo', 'expo-camera: preview flickers on Android when orientation changes', 'The camera preview flickers and briefly shows a black frame on Android 13+ when the device orientation changes while the camera is active.', 'TypeScript', 'intermediate', ['android', 'bug']],
        ['react-navigation/react-navigation', 'Stack navigator: back gesture should dismiss keyboard on iOS', 'When a text input is focused and the user starts the back swipe gesture the keyboard should dismiss as the screen slides away. Currently it stays visible until the transition completes.', 'TypeScript', 'beginner', ['ios', 'good first issue']],
        ['marmelab/react-admin', 'Datagrid: column widths should be user-resizable', 'Adding drag-to-resize columns to Datagrid would make it much more usable for tables with mixed content widths. Should persist to localStorage optionally.', 'TypeScript', 'advanced', ['enhancement', 'ui']],
        ['reduxjs/redux-toolkit', 'RTK Query: improve TypeScript inference for `providesTags` returning a function', 'When `providesTags` is defined as a function returning a computed tag list the inferred type does not match the TagDescription constraint and requires a cast.', 'TypeScript', 'intermediate', ['typescript', 'rtk-query']],
        ['nestjs/nest', 'Add `@ApiPropertyOptional` shorthand for deeply nested optional schemas', 'When documenting deeply nested optional objects with Swagger every level requires explicit `@ApiPropertyOptional`. Should support a `partial: true` option on `@ApiProperty` for nested schemas.', 'TypeScript', 'beginner', ['swagger', 'good first issue']],
    ];

    /** Sara's completed contributions (shown on her profile Contributions tab) */
    private const SARA_CONTRIBUTIONS = [
        ['figma-community/tokens-studio', 28, 'Fix token resolver ignoring composite token references', 'JavaScript', ['bug', 'good first issue']],
        ['storybookjs/storybook', 91, 'Vue 3: defineComponent wrapper breaks story controls inference', 'TypeScript', ['bug', 'vue']],
        ['stitchesjs/stitches', 14, 'Add documentation for responsive variants with TypeScript generics', 'TypeScript', ['documentation']],
    ];

    public function run(): void
    {
        $sara      = User::where('email', DemoUserProfileSeeder::SARA_EMAIL)->firstOrFail();
        $teammates = User::whereIn('email', array_column(DemoUserProfileSeeder::TEAMMATES, 'email'))
            ->orderBy('id')->get()->values();

        // ── 1. Open issues for the /contribute page ───────────────────────────
        $issueNumber = GithubIssue::max('issue_number') + 1;
        foreach (self::OPEN_ISSUES as $row) {
            [$repo, $issueTitle, $body, $lang, $diff, $labels] = $row;

            GithubIssue::firstOrCreate(
                ['repo_full_name' => $repo, 'title' => $issueTitle],
                [
                    'issue_number' => $issueNumber++,
                    'body'         => $body,
                    'url'          => "https://github.com/{$repo}/issues/{$issueNumber}",
                    'labels'       => $labels,
                    'language'     => $lang,
                    'difficulty'   => $diff,
                    'state'        => GithubIssue::STATE_OPEN,
                    'closed_at'    => null,
                    'fetched_at'   => Carbon::now(),
                ]
            );
        }

        // ── 2. Sara's merged contributions (profile page) ─────────────────────
        foreach (self::SARA_CONTRIBUTIONS as [$repo, $num, $issueTitle, $lang, $labels]) {
            $issue = GithubIssue::firstOrCreate(
                ['repo_full_name' => $repo, 'issue_number' => $num],
                [
                    'title'      => $issueTitle,
                    'body'       => "Issue: {$issueTitle}",
                    'url'        => "https://github.com/{$repo}/issues/{$num}",
                    'labels'     => $labels,
                    'language'   => $lang,
                    'difficulty' => 'intermediate',
                    'state'      => GithubIssue::STATE_CLOSED,
                    'closed_at'  => Carbon::now()->subDays(rand(30, 90)),
                    'fetched_at' => Carbon::now(),
                ]
            );

            ContributionLog::firstOrCreate(
                ['user_id' => $sara->id, 'github_issue_id' => $issue->id],
                [
                    'status' => ContributionLog::STATUS_MERGED,
                    'pr_url' => "https://github.com/{$repo}/pull/{$num}",
                ]
            );
        }

        // ── 3. Teammate contributions (Ahmad, Lara, Omar, Maya) ───────────────
        $teammateContributions = [
            'ahmad.nasser@demo.io' => [
                ['laravel/framework', 201, 'Fix `whereHas` with polymorphic relations on PostgreSQL', 'PHP', ['bug']],
                ['laravel/framework', 202, 'Add `chunkById` support for update queries', 'PHP', ['enhancement']],
                ['inertiajs/inertia', 203, 'Improve SSR hydration warning message', 'TypeScript', ['dx', 'good first issue']],
            ],
            'lara.haddad@demo.io' => [
                ['vuejs/core', 204, 'Fix computed watcher not triggering on nested reactive arrays', 'TypeScript', ['bug']],
                ['tailwindlabs/tailwindcss', 205, 'Add `line-clamp-none` utility', 'CSS', ['enhancement', 'good first issue']],
            ],
            'omar.fayad@demo.io' => [
                ['vercel/next.js', 206, 'Document cache invalidation patterns for server components', 'TypeScript', ['documentation', 'good first issue']],
            ],
            'maya.rizk@demo.io' => [
                ['ant-design/ant-design', 207, 'Add `aria-describedby` to Tooltip when description prop is set', 'TypeScript', ['accessibility', 'good first issue']],
                ['chakra-ui/chakra-ui', 208, 'Improve contrast ratio of disabled button state in dark mode', 'TypeScript', ['accessibility']],
            ],
        ];

        foreach ($teammateContributions as $email => $contribs) {
            $user = $teammates->firstWhere('email', $email);
            if (!$user) continue;

            foreach ($contribs as [$repo, $num, $issueTitle, $lang, $labels]) {
                $issue = GithubIssue::firstOrCreate(
                    ['repo_full_name' => $repo, 'issue_number' => $num],
                    [
                        'title'      => $issueTitle,
                        'body'       => "Issue: {$issueTitle}",
                        'url'        => "https://github.com/{$repo}/issues/{$num}",
                        'labels'     => $labels,
                        'language'   => $lang,
                        'difficulty' => 'beginner',
                        'state'      => GithubIssue::STATE_CLOSED,
                        'closed_at'  => Carbon::now()->subDays(rand(15, 60)),
                        'fetched_at' => Carbon::now(),
                    ]
                );

                ContributionLog::firstOrCreate(
                    ['user_id' => $user->id, 'github_issue_id' => $issue->id],
                    [
                        'status' => ContributionLog::STATUS_MERGED,
                        'pr_url' => "https://github.com/{$repo}/pull/{$num}",
                    ]
                );
            }
        }

        // ── 4. Sara has one issue she is actively working on ──────────────────
        $wip = GithubIssue::firstOrCreate(
            ['repo_full_name' => 'storybookjs/storybook', 'issue_number' => 301],
            [
                'title'      => 'Improve empty state for canvas panel when no stories match filters',
                'body'       => 'When story filters return zero results the canvas shows a blank white area with no message. Should show an empty state with a clear description and a reset button.',
                'url'        => 'https://github.com/storybookjs/storybook/issues/301',
                'labels'     => ['ux', 'enhancement'],
                'language'   => 'TypeScript',
                'difficulty' => 'beginner',
                'state'      => GithubIssue::STATE_OPEN,
                'closed_at'  => null,
                'fetched_at' => Carbon::now(),
            ]
        );

        ContributionLog::firstOrCreate(
            ['user_id' => $sara->id, 'github_issue_id' => $wip->id],
            [
                'status' => ContributionLog::STATUS_PR_SUBMITTED,
                'pr_url' => 'https://github.com/storybookjs/storybook/pull/302',
            ]
        );
    }
}
