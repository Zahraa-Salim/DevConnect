<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GitHubController extends Controller
{
    public function linkRepo(Request $request, Project $project): RedirectResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);

        $validated = $request->validate([
            'repo_url' => [
                'required',
                'string',
                'url',
                'max:500',
                'regex:/^https?:\/\/(www\.)?github\.com\/[\w.\-]+\/[\w.\-]+/',
            ],
        ]);

        $project->update(['repo_url' => $validated['repo_url']]);

        Cache::forget("github_commits_{$project->id}");
        Cache::forget("github_prs_{$project->id}");
        Cache::forget("github_contributors_{$project->id}");

        return back()->with('success', 'GitHub repository linked');
    }

    public function unlinkRepo(Project $project): RedirectResponse
    {
        abort_if(auth()->id() !== $project->owner_id, 403);

        $project->update(['repo_url' => null]);

        Cache::forget("github_commits_{$project->id}");
        Cache::forget("github_prs_{$project->id}");
        Cache::forget("github_contributors_{$project->id}");

        return back()->with('success', 'GitHub repository unlinked');
    }

    public function commits(Project $project): JsonResponse
    {
        abort_if(! $this->isMemberOrOwner($project), 403);
        abort_if(! $project->repo_url, 400, 'No GitHub repository linked');

        $cacheKey = "github_commits_{$project->id}";
        $commits = Cache::remember($cacheKey, 300, function () use ($project) {
            $repo = $this->parseRepoFromUrl($project->repo_url);

            $response = Http::withHeaders($this->getGitHubHeaders($project))
                ->get("https://api.github.com/repos/{$repo['owner']}/{$repo['repo']}/commits", [
                    'per_page' => 15,
                ]);

            if (! $response->successful()) {
                return ['error' => 'Failed to fetch commits: ' . $response->status()];
            }

            return collect($response->json())->map(fn ($c) => [
                'sha' => substr($c['sha'], 0, 7),
                'full_sha' => $c['sha'],
                'message' => Str::limit($c['commit']['message'], 120),
                'author_name' => $c['commit']['author']['name'] ?? 'Unknown',
                'author_avatar' => $c['author']['avatar_url'] ?? null,
                'author_login' => $c['author']['login'] ?? null,
                'date' => $c['commit']['author']['date'],
                'url' => $c['html_url'],
            ])->toArray();
        });

        return response()->json($commits);
    }

    public function pullRequests(Project $project): JsonResponse
    {
        abort_if(! $this->isMemberOrOwner($project), 403);
        abort_if(! $project->repo_url, 400, 'No GitHub repository linked');

        $cacheKey = "github_prs_{$project->id}";
        $prs = Cache::remember($cacheKey, 300, function () use ($project) {
            $repo = $this->parseRepoFromUrl($project->repo_url);

            $response = Http::withHeaders($this->getGitHubHeaders($project))
                ->get("https://api.github.com/repos/{$repo['owner']}/{$repo['repo']}/pulls", [
                    'state' => 'open',
                    'per_page' => 10,
                ]);

            if (! $response->successful()) {
                return ['error' => 'Failed to fetch PRs: ' . $response->status()];
            }

            return collect($response->json())->map(fn ($pr) => [
                'number' => $pr['number'],
                'title' => Str::limit($pr['title'], 100),
                'author_login' => $pr['user']['login'] ?? 'Unknown',
                'author_avatar' => $pr['user']['avatar_url'] ?? null,
                'created_at' => $pr['created_at'],
                'url' => $pr['html_url'],
                'labels' => collect($pr['labels'] ?? [])->pluck('name')->toArray(),
                'draft' => $pr['draft'] ?? false,
            ])->toArray();
        });

        return response()->json($prs);
    }

    public function contributors(Project $project): JsonResponse
    {
        abort_if(! $this->isMemberOrOwner($project), 403);
        abort_if(! $project->repo_url, 400, 'No GitHub repository linked');

        $cacheKey = "github_contributors_{$project->id}";
        $contributors = Cache::remember($cacheKey, 600, function () use ($project) {
            $repo = $this->parseRepoFromUrl($project->repo_url);

            $response = Http::withHeaders($this->getGitHubHeaders($project))
                ->get("https://api.github.com/repos/{$repo['owner']}/{$repo['repo']}/contributors", [
                    'per_page' => 20,
                ]);

            if (! $response->successful()) {
                return ['error' => 'Failed to fetch contributors: ' . $response->status()];
            }

            return collect($response->json())->map(fn ($c) => [
                'login' => $c['login'],
                'avatar_url' => $c['avatar_url'],
                'contributions' => $c['contributions'],
                'url' => $c['html_url'],
            ])->toArray();
        });

        return response()->json($contributors);
    }

    private function parseRepoFromUrl(string $url): array
    {
        $path = parse_url($url, PHP_URL_PATH);
        $parts = array_values(array_filter(explode('/', trim($path, '/'))));
        if (count($parts) < 2) {
            throw new \RuntimeException('Invalid GitHub URL');
        }

        return ['owner' => $parts[0], 'repo' => str_replace('.git', '', $parts[1])];
    }

    private function getGitHubHeaders(Project $project): array
    {
        $headers = [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'DevConnect-LB',
        ];

        $ownerToken = $project->owner?->github_token;
        if ($ownerToken) {
            try {
                $decrypted = decrypt($ownerToken);
                $headers['Authorization'] = "Bearer {$decrypted}";
            } catch (\Exception $e) {
                // Token decryption failed, proceed without auth
            }
        }

        return $headers;
    }

    private function isMemberOrOwner(Project $project): bool
    {
        if (! auth()->check()) {
            return false;
        }

        if (auth()->id() === $project->owner_id) {
            return true;
        }

        return $project->members()
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->exists();
    }
}
