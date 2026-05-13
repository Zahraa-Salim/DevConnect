<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Timestamped team decisions per project.
 */
class DecisionLog extends Model
{
    use HasFactory;

    protected $table = 'decision_log';

    protected $fillable = [
        'project_id',
        'user_id',
        'decision',
        'reason',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
