<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Async help requests sent to mentors with code context.
 */
class HelpRequest extends Model
{
    use HasFactory;

    // Status constants (v1)
    public const STATUS_PENDING   = 'pending';
    public const STATUS_ACCEPTED  = 'accepted';
    public const STATUS_RESPONDED = 'responded';
    public const STATUS_CLOSED    = 'closed';
    public const STATUS_DECLINED  = 'declined';
    // Status constants (v2)
    public const STATUS_OPEN        = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED    = 'resolved';

    protected $fillable = [
        'user_id',
        'project_id',
        'title',
        'description',
        'tech_tags',
        'status',
        'requester_id',
        'mentor_id',
        'context',
        'stack',
        'code_snippet',
        'response',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'tech_tags' => 'array',
            'responded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /** Public comments / answers from any community member. */
    public function comments(): HasMany
    {
        return $this->hasMany(HelpRequestComment::class)->orderBy('created_at');
    }
}
