<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    /**
     * tutor_id is deliberately NOT mass-assignable: it is always set
     * server-side from the acting tutor, never from request input.
     */
    protected $fillable = [
        'student_id',
        'lesson_date',
        'bill_id',
    ];

    protected function casts(): array
    {
        return [
            // 'date:Y-m-d' keeps the stored value a bare date string (no 00:00:00 suffix on
            // SQLite), so it compares equal to the raw 'Y-m-d' strings used in whereIn/insertOrIgnore.
            'lesson_date' => 'date:Y-m-d',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
