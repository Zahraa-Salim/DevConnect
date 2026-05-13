<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Core identity. Every authenticated user.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // Role constants
    public const ROLE_DEV = 'dev';
    public const ROLE_DESIGNER = 'designer';
    public const ROLE_PM = 'pm';
    public const ROLE_MENTOR = 'mentor';
    public const ROLE_EXPLORING = 'exploring';

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'github_username',
        'github_token',
        'github_synced_at',
        'bio',
        'avatar_url',
        'role',
        'is_available',
        'is_verified',
        'is_admin',
        'reputation_score',
        'contribution_dna',
        'notifications_read_at',
        'suspended_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'github_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'github_token' => 'encrypted',
            'github_synced_at' => 'datetime',
            'is_available' => 'boolean',
            'is_verified' => 'boolean',
            'is_admin' => 'boolean',
            'reputation_score' => 'decimal:2',
            'contribution_dna' => 'array',
            'notifications_read_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function getReputationScoreAttribute($value): float
    {
        return (float) $value;
    }

    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    // ============ RELATIONSHIPS ============

    public function workingStyle(): HasOne
    {
        return $this->hasOne(WorkingStyle::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(UserSkill::class);
    }

    public function roleDiscoveryAnswers(): HasMany
    {
        return $this->hasMany(RoleDiscoveryAnswer::class);
    }

    public function submittedIdeas(): HasMany
    {
        return $this->hasMany(ProjectIdea::class, 'submitted_by');
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function decisionsLogged(): HasMany
    {
        return $this->hasMany(DecisionLog::class);
    }

    public function aliveSignals(): HasMany
    {
        return $this->hasMany(AliveSignal::class);
    }

    public function uploadedFiles(): HasMany
    {
        return $this->hasMany(ProjectFile::class, 'uploaded_by');
    }

    public function conversationParticipations(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function contributionLogs(): HasMany
    {
        return $this->hasMany(ContributionLog::class);
    }

    public function contributionDna(): HasOne
    {
        return $this->hasOne(ContributionDna::class);
    }

    public function ratedRatings(): HasMany
    {
        return $this->hasMany(Rating::class, 'rated_id');
    }

    public function ratingsReceived(): HasMany
    {
        return $this->hasMany(Rating::class, 'rated_id');
    }

    public function givenRatings(): HasMany
    {
        return $this->hasMany(Rating::class, 'rater_id');
    }

    public function receivedEndorsements(): HasMany
    {
        return $this->hasMany(SkillEndorsement::class, 'endorsed_id');
    }

    public function givenEndorsements(): HasMany
    {
        return $this->hasMany(SkillEndorsement::class, 'endorser_id');
    }

    public function aiSuggestions(): HasMany
    {
        return $this->hasMany(AiSuggestion::class);
    }

    public function mentorProfile(): HasOne
    {
        return $this->hasOne(MentorProfile::class);
    }

    public function mentorProjects(): HasMany
    {
        return $this->hasMany(MentorProject::class, 'mentor_id');
    }

    public function officeHours(): HasMany
    {
        return $this->hasMany(OfficeHour::class, 'mentor_id');
    }

    public function mentorSlots(): HasMany
    {
        return $this->hasMany(MentorSlot::class, 'mentor_id');
    }

    public function helpRequestsSent(): HasMany
    {
        return $this->hasMany(HelpRequest::class, 'requester_id');
    }

    public function helpRequestsReceived(): HasMany
    {
        return $this->hasMany(HelpRequest::class, 'mentor_id');
    }

    public function reportsSubmitted(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function auditLogsAsActor(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members');
    }

    public function tasksAssigned(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function chemistryScoresTriggered(): HasMany
    {
        return $this->hasMany(ChemistryScore::class, 'triggered_by');
    }

    public function aiUsageLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class);
    }

    public function officeHourBookingsMade(): HasMany
    {
        return $this->hasMany(OfficeHourBooking::class, 'booker_id');
    }

    public function resolvedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'resolved_by');
    }
}
