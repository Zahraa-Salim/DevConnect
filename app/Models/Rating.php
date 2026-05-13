<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Multi-dimensional teammate ratings after project completion.
 */
class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'rater_id',
        'rated_id',
        'communication_score',
        'reliability_score',
        'contribution_score',
        'overall_score',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'communication_score' => 'integer',
            'reliability_score' => 'integer',
            'contribution_score' => 'integer',
            'overall_score' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function rated(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_id');
    }
}
