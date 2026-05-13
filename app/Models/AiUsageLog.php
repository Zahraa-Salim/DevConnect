<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Per-call audit log for every Claude API call. Cost + abuse tracking.
 */
class AiUsageLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    // Feature constants
    public const FEATURE_IDEA_GEN = 'idea_gen';
    public const FEATURE_PROJECT_MATCH = 'project_match';
    public const FEATURE_ISSUE_MATCH = 'issue_match';
    public const FEATURE_PROFILE_SUGGEST = 'profile_suggest';
    public const FEATURE_CHEMISTRY = 'chemistry';
    public const FEATURE_DNA = 'dna';
    public const FEATURE_TASK_GEN = 'task_gen';

    // Status constants
    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'user_id',
        'project_id',
        'feature',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'latency_ms',
        'status',
        'error_message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
