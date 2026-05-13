<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Mentor-specific data. 1:1 with users. Only mentors have a row.
 */
class MentorProfile extends Model
{
    use HasFactory;

    // Status constants (v1)
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_REVOKED = 'revoked';
    // Status constants (v2)
    public const STATUS_PENDING = 'pending';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'motivation',
        'focus_areas',
        'availability',
        'is_active',
        'status',
        'github_score',
        'approved_at',
        'rejected_at',
        'experience_years',
        'topics',
        'domains',
        'hours_per_week',
        'avg_rating',
        'sessions_completed',
        'projects_advised',
        'reapply_after',
        'auto_approved_at',
    ];

    protected function casts(): array
    {
        return [
            'focus_areas' => 'array',
            'availability' => 'array',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'topics' => 'array',
            'domains' => 'array',
            'avg_rating' => 'decimal:2',
            'reapply_after' => 'date',
            'auto_approved_at' => 'datetime',
            'experience_years' => 'integer',
            'hours_per_week' => 'integer',
            'github_score' => 'integer',
            'sessions_completed' => 'integer',
            'projects_advised' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(MentorSlot::class, 'mentor_profile_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(MentorProject::class, 'mentor_id');
    }

    public function officeHours(): HasMany
    {
        return $this->hasMany(OfficeHour::class, 'mentor_id');
    }
}
