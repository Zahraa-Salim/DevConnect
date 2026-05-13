<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * NDAs and team agreements signed for Real Client Projects.
 */
class TeamAgreement extends Model
{
    use HasFactory;

    // Type constants
    public const TYPE_NDA = 'nda';
    public const TYPE_TEAM_AGREEMENT = 'team_agreement';

    protected $fillable = [
        'project_id',
        'user_id',
        'type',
        'document_text',
        'signed_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
