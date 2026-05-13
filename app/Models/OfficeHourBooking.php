<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Confirmed/pending bookings of mentor slots.
 */
class OfficeHourBooking extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    protected $fillable = [
        'mentor_slot_id',
        'booker_id',
        'status',
        'session_topic',
        'outcome_notes',
        'rating',
        'rating_comment',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function mentorSlot(): BelongsTo
    {
        return $this->belongsTo(MentorSlot::class);
    }

    public function booker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booker_id');
    }
}
