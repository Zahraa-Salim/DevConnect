<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * All projects (practice or real_client). Hub for tasks, chat, files, members.
 */
class Project extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_OPEN = 'open';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_AT_RISK = 'at_risk';
    public const STATUS_ARCHIVED = 'archived';

    // Type constants
    public const TYPE_PRACTICE = 'practice';
    public const TYPE_REAL_CLIENT = 'real_client';

    protected $fillable = [
        'owner_id',
        'idea_id',
        'title',
        'description',
        'status',
        'type',
        'domain',
        'tech_stack',
        'repo_url',
        'max_members',
        'estimated_duration',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'tech_stack' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    // ============ RELATIONSHIPS ============

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function idea(): BelongsTo
    {
        return $this->belongsTo(ProjectIdea::class, 'idea_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(ProjectRole::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('order_index');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function teamAgreements(): HasMany
    {
        return $this->hasMany(TeamAgreement::class);
    }

    public function inviteLinks(): HasMany
    {
        return $this->hasMany(InviteLink::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function decisionsLogged(): HasMany
    {
        return $this->hasMany(DecisionLog::class);
    }

    public function aliveSignals(): HasMany
    {
        return $this->hasMany(AliveSignal::class);
    }

    public function pulseLogs(): HasMany
    {
        return $this->hasMany(ProjectPulseLog::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function skillEndorsements(): HasMany
    {
        return $this->hasMany(SkillEndorsement::class);
    }

    public function chemistryScores(): HasMany
    {
        return $this->hasMany(ChemistryScore::class);
    }

    public function mentorProjects(): HasMany
    {
        return $this->hasMany(MentorProject::class);
    }

    public function convertedContributions(): HasMany
    {
        return $this->hasMany(ContributionLog::class, 'converted_project_id');
    }
}
