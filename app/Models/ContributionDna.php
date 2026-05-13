<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Behavioral fingerprint per user. 1:1 with users. Updated by scheduler.
 */
class ContributionDna extends Model
{
    use HasFactory;

    protected $table = 'contribution_dna';

    const CREATED_AT = null;

    protected $fillable = [
        'user_id',
        'role_pattern',
        'phase_activity',
        'issue_type_preference',
        'work_pattern',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'role_pattern' => 'array',
            'phase_activity' => 'array',
            'issue_type_preference' => 'array',
            'work_pattern' => 'array',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
