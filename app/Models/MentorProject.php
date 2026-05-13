<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Pivot — mentor advising a project. NOTE: advisor_conversation_id FK is DEFERRED.
 */
class MentorProject extends Model
{
    use HasFactory;

    protected $table = 'mentor_project';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_ENDED = 'ended';

    protected $fillable = [
        'mentor_id',
        'project_id',
        'advisor_conversation_id',
        'status',
        'advisory_notes',
        'joined_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function advisorConversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'advisor_conversation_id');
    }
}
