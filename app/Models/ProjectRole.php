<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Open and filled roles per project.
 */
class ProjectRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'role_name',
        'slots',
        'filled',
        'is_open',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'role_id');
    }

    public function inviteLinks(): HasMany
    {
        return $this->hasMany(InviteLink::class, 'role_id');
    }
}
