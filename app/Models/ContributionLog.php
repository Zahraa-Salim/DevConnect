<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Solo contribution tracker. One per (user, issue).
 */
class ContributionLog extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_BOOKMARKED = 'bookmarked';
    public const STATUS_WORKING = 'working';
    public const STATUS_PR_SUBMITTED = 'pr_submitted';
    public const STATUS_MERGED = 'merged';

    protected $fillable = [
        'user_id',
        'github_issue_id',
        'status',
        'pr_url',
        'converted_project_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(GithubIssue::class, 'github_issue_id');
    }

    public function convertedProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'converted_project_id');
    }
}
