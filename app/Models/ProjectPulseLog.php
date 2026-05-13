<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Health snapshots from Project Pulse scheduler.
 */
class ProjectPulseLog extends Model
{
    use HasFactory;

    protected $table = 'project_pulse_log';

    const UPDATED_AT = null;

    // Status constants
    public const STATUS_NUDGE_SENT = 'nudge_sent';
    public const STATUS_AT_RISK = 'at_risk';
    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'project_id',
        'signals',
        'status',
        'triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'signals' => 'array',
            'triggered_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
