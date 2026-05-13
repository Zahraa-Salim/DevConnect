<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Cached GitHub issues from daily sync. Source for AI matching.
 */
class GithubIssue extends Model
{
    use HasFactory;

    const CREATED_AT = null;
    const UPDATED_AT = null;

    // Difficulty constants
    public const DIFFICULTY_BEGINNER = 'beginner';
    public const DIFFICULTY_INTERMEDIATE = 'intermediate';
    public const DIFFICULTY_ADVANCED = 'advanced';

    // State constants
    public const STATE_OPEN = 'open';
    public const STATE_CLOSED = 'closed';

    protected $fillable = [
        'repo_full_name',
        'issue_number',
        'title',
        'body',
        'url',
        'labels',
        'language',
        'difficulty',
        'state',
        'closed_at',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'closed_at' => 'datetime',
            'fetched_at' => 'datetime',
        ];
    }

    public function contributionLogs(): HasMany
    {
        return $this->hasMany(ContributionLog::class);
    }
}
