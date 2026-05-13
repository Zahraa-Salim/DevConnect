<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MentorBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'mentor_slot_id',
        'student_id',
        'topic',
        'cancellation_reason',
    ];

    public function slot(): BelongsTo
    {
        return $this->belongsTo(MentorSlot::class, 'mentor_slot_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
