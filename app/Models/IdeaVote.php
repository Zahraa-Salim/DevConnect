<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * One vote per user per idea. Replaces naive upvote integer.
 */
class IdeaVote extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'idea_id',
        'user_id',
    ];

    public function idea(): BelongsTo
    {
        return $this->belongsTo(ProjectIdea::class, 'idea_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
