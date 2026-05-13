<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Teammate-witnessed skill endorsements during a project.
 */
class SkillEndorsement extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'endorser_id',
        'endorsed_id',
        'skill_name',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function endorser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'endorser_id');
    }

    public function endorsed(): BelongsTo
    {
        return $this->belongsTo(User::class, 'endorsed_id');
    }
}
