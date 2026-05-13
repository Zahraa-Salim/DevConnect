<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Quiz answers for users selecting 'exploring'. Outputs suggested_role.
 */
class RoleDiscoveryAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'answers',
        'suggested_role',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
