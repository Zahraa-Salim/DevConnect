<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Pivot: users participating in projects.
 */
class ProjectMember extends Model
{
    use HasFactory;

    public $timestamps = false;

    // Status constants
    public const STATUS_UNDECIDED = 'undecided';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_LEFT = 'left';
    public const STATUS_REMOVED = 'removed';

    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'status',
        'access_level',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
