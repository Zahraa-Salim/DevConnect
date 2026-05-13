<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Snapshots of team compatibility when new member joins.
 */
class ChemistryScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'triggered_by',
        'score_data',
    ];

    protected function casts(): array
    {
        return [
            'score_data' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
