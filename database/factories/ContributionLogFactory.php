<?php

namespace Database\Factories;

use App\Models\ContributionLog;
use App\Models\GithubIssue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContributionLog>
 */
class ContributionLogFactory extends Factory
{
    public function definition(): array
    {
        $uid   = substr(str_replace('-', '', Str::uuid()), 0, 8);
        $repo  = "devlb{$uid}/" . fake()->slug(2);
        $issueNum = fake()->numberBetween(1, 999);

        $issue = GithubIssue::create([
            'repo_full_name' => $repo,
            'issue_number'   => $issueNum,
            'title'          => fake()->sentence(6),
            'body'           => fake()->paragraph(),
            'url'            => "https://github.com/{$repo}/issues/{$issueNum}",
            'labels'         => ['good first issue'],
            'language'       => fake()->randomElement(['PHP', 'JavaScript', 'Python', 'TypeScript']),
            'difficulty'     => 'beginner',
            'state'          => 'closed',
            'closed_at'      => now()->subDays(fake()->numberBetween(1, 90)),
            'fetched_at'     => now(),
        ]);

        return [
            'user_id'              => User::factory(),
            'github_issue_id'      => $issue->id,
            'status'               => 'merged',
            'pr_url'               => "https://github.com/{$repo}/pull/" . fake()->numberBetween(1, 100),
            'converted_project_id' => null,
        ];
    }
}
