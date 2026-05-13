<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Individual bookable time slots. Replaces unstructured JSON.
 */
class MentorSlot extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_AVAILABLE  = 'available';
    public const STATUS_BOOKED     = 'booked';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_CANCELLED  = 'cancelled';

    protected $fillable = [
        'mentor_profile_id',
        'starts_at',
        'ends_at',
        'status',
        'mentor_id',
        'office_hour_id',
        'slot_datetime',
        'is_booked',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'slot_datetime' => 'datetime',
            'is_booked' => 'boolean',
        ];
    }

    public function mentorProfile(): BelongsTo
    {
        return $this->belongsTo(MentorProfile::class, 'mentor_profile_id');
    }

    public function booking(): HasOne
    {
        return $this->hasOne(MentorBooking::class, 'mentor_slot_id');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function officeHour(): BelongsTo
    {
        return $this->belongsTo(OfficeHour::class);
    }
}
