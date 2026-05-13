<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Structured working style per user. 1:1 with users. Required by Team Chemistry AI.
 */
class WorkingStyle extends Model
{
    use HasFactory;

    // Communication preference constants
    public const COMMUNICATION_ASYNC = 'async';
    public const COMMUNICATION_SYNC = 'sync';
    public const COMMUNICATION_MIXED = 'mixed';

    // Feedback style constants
    public const FEEDBACK_DIRECT = 'direct';
    public const FEEDBACK_GENTLE = 'gentle';
    public const FEEDBACK_STRUCTURED = 'structured';

    // Conflict approach constants
    public const CONFLICT_DISCUSS = 'discuss';
    public const CONFLICT_VOTE = 'vote';
    public const CONFLICT_DEFER = 'defer';

    protected $fillable = [
        'user_id',
        'communication_pref',
        'work_hours_start',
        'work_hours_end',
        'timezone',
        'feedback_style',
        'conflict_approach',
        'weekly_hours',
    ];

    protected function casts(): array
    {
        return [
            // TIME columns return 'HH:MM:SS' strings — no cast needed
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
