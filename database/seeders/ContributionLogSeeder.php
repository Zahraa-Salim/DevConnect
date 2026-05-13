<?php

namespace Database\Seeders;

use App\Models\ContributionLog;
use App\Models\GithubIssue;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContributionLogSeeder extends Seeder
{
    public function run(): void
    {
        $targets = [
            'rami@devconnect.lb'  => ['ramihaddad/devtrack',    ['Fix pagination on dashboard', 'Add GitHub sync for portfolios', 'Improve cache invalidation logic']],
            'lara@devconnect.lb'  => ['larakhoury/vuekit',      ['Fix router navigation guard bug', 'Add dark mode toggle component', 'Refactor composable for skill badges']],
            'tarek@devconnect.lb' => ['tareknassar/figma-tools', ['Resolve SVG export quality issue', 'Add design token generation script']],
            'user6@devconnect.lb' => ['devlb6/react-starter',   ['Fix TypeScript generic error in hooks', 'Add unit tests for auth reducer', 'Implement lazy loading for routes']],
            'user7@devconnect.lb' => ['devlb7/python-utils',    ['Resolve async context manager bug', 'Add retry logic to HTTP client']],
        ];

        $issueCounter = 1;

        foreach ($targets as $email => [$repo, $issueTitles]) {
            $user = User::where('email', $email)->first();

            foreach ($issueTitles as $title) {
                $issueNum = $issueCounter++;

                $issue = GithubIssue::create([
                    'repo_full_name' => $repo,
                    'issue_number'   => $issueNum,
                    'title'          => $title,
                    'body'           => "Bug report: {$title}. Steps to reproduce included in the issue thread.",
                    'url'            => "https://github.com/{$repo}/issues/{$issueNum}",
                    'labels'         => ['bug', 'good first issue'],
                    'language'       => $this->guessLanguage($repo),
                    'difficulty'     => 'beginner',
                    'state'          => 'closed',
                    'closed_at'      => now()->subDays(rand(5, 85)),
                    'fetched_at'     => now(),
                ]);

                ContributionLog::create([
                    'user_id'         => $user->id,
                    'github_issue_id' => $issue->id,
                    'status'          => 'merged',
                    'pr_url'          => "https://github.com/{$repo}/pull/{$issueNum}",
                    'converted_project_id' => null,
                ]);
            }
        }
    }

    private function guessLanguage(string $repo): string
    {
        if (str_contains($repo, 'python')) return 'Python';
        if (str_contains($repo, 'react'))  return 'JavaScript';
        if (str_contains($repo, 'figma'))  return 'JavaScript';
        if (str_contains($repo, 'vue'))    return 'JavaScript';
        return 'PHP';
    }
}
