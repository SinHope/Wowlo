<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hangman Wheel Panda — a spinnable wheel of free-text slices (see
 * docs/hangman-wheel-panda.md).
 *
 * 'standard' wheels are global and authored ONLY by the super_admin (tutor_id
 * NULL). 'custom' wheels are owned by a tutor (tutor_id) and seen only by that
 * tutor + their own students. `slices` is a plain list of label strings.
 */
class HangmanWheel extends Model
{
    protected $fillable = [
        'name', 'type', 'created_by', 'tutor_id', 'slices',
    ];

    protected function casts(): array
    {
        return [
            'slices' => 'array',
        ];
    }

    /** The user who authored the wheel. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** The owning tutor (custom wheels only; NULL for standard). */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function isStandard(): bool
    {
        return $this->type === 'standard';
    }

    /**
     * The wheels a given user may PLAY with: every standard wheel, plus the
     * custom wheels in their tenant. For a student that's their owning tutor's
     * wheels; for a tutor/super_admin it's their own custom wheels.
     */
    public function scopeAvailableTo(Builder $query, User $user): Builder
    {
        $tenantTutorId = $user->isStudent() ? $user->tutor_id : $user->id;

        return $query->where(function (Builder $q) use ($tenantTutorId) {
            $q->where('type', 'standard');

            if ($tenantTutorId !== null) {
                $q->orWhere(function (Builder $q) use ($tenantTutorId) {
                    $q->where('type', 'custom')->where('tutor_id', $tenantTutorId);
                });
            }
        });
    }
}
