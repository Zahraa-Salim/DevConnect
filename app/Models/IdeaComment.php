<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Comments on community ideas.
 */
class IdeaComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'idea_id',
        'user_id',
        'body',
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
