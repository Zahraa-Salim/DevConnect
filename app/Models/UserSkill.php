<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Self-reported and endorsed skills.
 */
class UserSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'skill_name',
        'proficiency',
        'is_endorsed',
        'endorsement_count',
    ];

    protected function casts(): array
    {
        return [
            'is_endorsed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
