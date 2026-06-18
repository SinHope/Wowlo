<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Homework extends Model
{
    // "homework" is uncountable, so Eloquent would guess the table as 'homework'.
    // Our migration uses the plural 'homeworks' (per spec), so pin it explicitly.
    protected $table = 'homeworks';

    protected $fillable = [
        'tutor_id',
        'student_id',
        'title',
        'subject',
        'description',
        'start_date',
        'due_date',
        'status',
        'completed_at',
        'feedback',
        'attachment_path',
        'attachment_name',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /** Student has claimed "I've done this" and is awaiting the tutor's check. */
    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    /** Tutor's verdict: checked and not done. */
    public function isNotDone(): bool
    {
        return $this->status === 'not_done';
    }

    /**
     * Derived (never stored): still awaiting the student and past its due date.
     * A 'submitted' claim is the tutor's to check, so it never reads as overdue.
     */
    public function isOverdue(): bool
    {
        return $this->isPending() && $this->due_date->lt(today());
    }

    public function hasAttachment(): bool
    {
        return filled($this->attachment_path);
    }
}
