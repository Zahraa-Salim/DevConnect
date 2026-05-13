<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * AI Profile Suggestions output (CV / portfolio / LinkedIn).
 */
class AiSuggestion extends Model
{
    use HasFactory;

    // Source type constants
    public const SOURCE_TYPE_PROJECT = 'project';
    public const SOURCE_TYPE_CONTRIBUTION = 'contribution';

    protected $fillable = [
        'user_id',
        'source_type',
        'source_id',
        'cv_text',
        'portfolio_text',
        'linkedin_text',
        'model',
        'tokens_used',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo('source', 'source_type', 'source_id');
    }
}
