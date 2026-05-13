<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubMentorScoringService
{
    private const HIGH_VALUE_LANGS = ['JavaScript', 'TypeScript', 'Python', 'PHP', 'Go'];

    public function score(User $user): int
    {
        if (! $user->github_username) {
            return 0;
        }

        try {
            $headers = $this->headers();

            $profileResp = Http::withHeaders($headers)
                ->timeout(10)
                ->get("https://api.github.com/users/{$user->github_username}");

            if (! $profileResp->successful()) {
                return 0;
            }

            $profile = $profileResp->json();

            $reposResp = Http::withHeaders($headers)
                ->timeout(10)
                ->get("https://api.github.com/users/{$user->github_username}/repos", [
                    'per_page' => 100,
                ]);

            $repos = $reposResp->successful() ? $reposResp->json() : [];

            return $this->calculate($profile, $repos);
        } catch (\Throwable $e) {
            Log::warning('GitHubMentorScoringService failed', [
                'user_id'  => $user->id,
                'username' => $user->github_username,
                'error'    => $e->getMessage(),
            ]);

            return 0;
        }
    }

    private function calculate(array $profile, array $repos): int
    {
        $score = 0;

        // Account age >= 2 years → +20
        if (isset($profile['created_at'])) {
            $ageYears = now()->diffInYears(new \DateTime($profile['created_at']));
            if ($ageYears >= 2) {
                $score += 20;
            }
        }

        // Public repos >= 10 → +20
        if (($profile['public_repos'] ?? 0) >= 10) {
            $score += 20;
        }

        // Followers >= 50 → +15
        if (($profile['followers'] ?? 0) >= 50) {
            $score += 15;
        }

        // Total stars > 50 → +20
        $totalStars = array_sum(array_column($repos, 'stargazers_count'));
        if ($totalStars > 50) {
            $score += 20;
        }

        // Bio not empty → +5
        if (! empty($profile['bio'])) {
            $score += 5;
        }

        // Has profile README repo → +10
        $username = $profile['login'] ?? '';
        $hasReadme = collect($repos)->contains(
            fn($r) => strtolower($r['name'] ?? '') === strtolower($username)
        );
        if ($hasReadme) {
            $score += 10;
        }

        // Primary language in high-value list → +10
        $langCounts = [];
        foreach ($repos as $repo) {
            $lang = $repo['language'] ?? null;
            if ($lang) {
                $langCounts[$lang] = ($langCounts[$lang] ?? 0) + 1;
            }
        }
        if (! empty($langCounts)) {
            arsort($langCounts);
            $primaryLang = array_key_first($langCounts);
            if (in_array($primaryLang, self::HIGH_VALUE_LANGS, true)) {
                $score += 10;
            }
        }

        return min(100, $score);
    }

    private function headers(): array
    {
        $headers = [
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'DevConnect-LB',
        ];

        $token = env('GITHUB_TOKEN');
        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return $headers;
    }
}
