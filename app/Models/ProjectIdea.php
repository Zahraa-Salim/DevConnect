<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * All idea sources (AI, community, curated) in one table.
 */
class ProjectIdea extends Model
{
    use HasFactory;

    // Source constants
    public const SOURCE_AI = 'ai';
    public const SOURCE_COMMUNITY = 'community';
    public const SOURCE_CURATED = 'curated';

    // Difficulty constants
    public const DIFFICULTY_BEGINNER = 'beginner';
    public const DIFFICULTY_INTERMEDIATE = 'intermediate';
    public const DIFFICULTY_ADVANCED = 'advanced';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_FEATURED = 'featured';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_REMOVED = 'removed';

    protected $fillable = [
        'source',
        'title',
        'description',
        'domain',
        'difficulty',
        'team_size',
        'suggested_roles',
        'requirements',
        'submitted_by',
        'status',
        'upvotes',
        'comments_count',
        'converted_project_id',
    ];

    protected function casts(): array
    {
        return [
            'suggested_roles' => 'array',
            'requirements' => 'array',
        ];
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(IdeaVote::class, 'idea_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IdeaComment::class, 'idea_id');
    }

    public function convertedProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'converted_project_id');
    }
}
