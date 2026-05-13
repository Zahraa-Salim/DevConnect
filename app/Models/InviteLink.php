<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Shareable invite links for project roles.
 */
class InviteLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'role_id',
        'token',
        'expires_at',
        'max_uses',
        'uses',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(ProjectRole::class, 'role_id');
    }
}
