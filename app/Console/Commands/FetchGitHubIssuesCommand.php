<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\GithubIssue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchGitHubIssuesCommand extends Command
{
    protected $signature = 'issues:fetch';
    protected $description = 'Fetch good-first-issue GitHub issues and cache them locally';

    private const LANGUAGES = [
        'javascript', 'typescript', 'python', 'php', 'java', 'go', 'rust', 'ruby',
    ];

    public function handle(): int
    {
        $token = env('GITHUB_TOKEN');
        $headers = ['Accept' => 'application/vnd.github+json'];
        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        $records = [];

        foreach (self::LANGUAGES as $lang) {
            $this->line("Fetching {$lang}...");

            try {
                $response = Http::withHeaders($headers)->get('https://api.github.com/search/issues', [
                    'q'        => "label:\"good first issue\"+language:{$lang}+state:open",
                    'sort'     => 'created',
                    'per_page' => 30,
                ]);
            } catch (\Exception $e) {
                $this->warn("  Failed: {$e->getMessage()}");
                Log::warning('issues:fetch network error', ['language' => $lang, 'error' => $e->getMessage()]);
                continue;
            }

            if (! $response->successful()) {
                $this->warn("  Failed: HTTP {$response->status()}");
                Log::warning('issues:fetch HTTP error', ['language' => $lang, 'status' => $response->status()]);
                continue;
            }

            foreach ($response->json('items', []) as $item) {
                $repoFullName = $this->parseRepoFullName($item['repository_url'] ?? '');
                if (! $repoFullName) {
                    continue;
                }

                $body        = $item['body'] ?? '';
                $bodyLength  = mb_strlen($body);
                $labelNames  = array_column($item['labels'] ?? [], 'name');

                $records[] = [
                    'repo_full_name' => $repoFullName,
                    'issue_number'   => $item['number'],
                    'title'          => mb_substr($item['title'] ?? '', 0, 500),
                    'body'           => mb_substr($body, 0, 500),
                    'url'            => $item['html_url'] ?? '',
                    'labels'         => json_encode($labelNames),
                    'language'       => $lang,
                    'difficulty'     => $this->estimateDifficulty($bodyLength, $labelNames),
                    'state'          => $item['state'] ?? 'open',
                    'fetched_at'     => now()->toDateTimeString(),
                ];
            }
        }

        if (empty($records)) {
            $this->warn('No issues fetched.');
            return Command::SUCCESS;
        }

        GithubIssue::upsert(
            $records,
            ['repo_full_name', 'issue_number'],
            ['title', 'body', 'url', 'labels', 'difficulty', 'state', 'fetched_at'],
        );

        Log::info('issues:fetch completed', ['upserted' => count($records)]);
        $this->info('Done. Upserted ' . count($records) . ' issues.');

        return Command::SUCCESS;
    }

    private function parseRepoFullName(string $repositoryUrl): ?string
    {
        if (! str_contains($repositoryUrl, '/repos/')) {
            return null;
        }
        return ltrim(str_replace('https://api.github.com/repos', '', $repositoryUrl), '/');
    }

    private function estimateDifficulty(int $bodyLength, array $labelNames): string
    {
        $lower = array_map('strtolower', $labelNames);

        if ($bodyLength < 300 || in_array('beginner', $lower)) {
            return GithubIssue::DIFFICULTY_BEGINNER;
        }
        if (in_array('advanced', $lower) || $bodyLength > 1500) {
            return GithubIssue::DIFFICULTY_ADVANCED;
        }
        return GithubIssue::DIFFICULTY_INTERMEDIATE;
    }
}
