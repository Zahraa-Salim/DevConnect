<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sprint extends Model
{
    use HasFactory;

    const STATUS_PLANNING = 'planning';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'project_id', 'name', 'goal', 'start_date', 'end_date',
        'status', 'velocity', 'retro_good', 'retro_improve', 'retro_actions',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function getDaysRemainingAttribute(): int
    {
        return max(0, (int) now()->startOfDay()->diffInDays($this->end_date, false));
    }

    public function getTotalPointsAttribute(): int
    {
        return (int) ($this->tasks()->sum('story_points') ?? 0);
    }

    public function getCompletedPointsAttribute(): int
    {
        return (int) ($this->tasks()->where('status', 'done')->sum('story_points') ?? 0);
    }
}
