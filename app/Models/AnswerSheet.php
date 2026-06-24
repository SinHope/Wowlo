<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnswerSheet extends Model
{
    protected $fillable = [
        'author_id', 'tutor_id', 'student_id', 'type', 'title', 'subject',
        'remarks', 'status', 'total_marks', 'obtained_marks', 'feedback',
        'submitted_at', 'marked_at',
    ];

    protected function casts(): array
    {
        return [
            'total_marks'    => 'integer',
            'obtained_marks' => 'float', // half-marks allowed when marking
            'submitted_at'   => 'datetime',
            'marked_at'      => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AnswerSheetQuestion::class)->orderBy('order');
    }

    public function isMcq(): bool
    {
        return $this->type === 'mcq';
    }

    public function isShortAnswer(): bool
    {
        return $this->type === 'short_answer';
    }

    /** Sent by a tutor, still waiting for the student to fill it in. */
    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    /** Filled in by the student, awaiting the tutor's manual marking. */
    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isMarked(): bool
    {
        return $this->status === 'marked';
    }

    public function typeLabel(): string
    {
        return config("wowlo.answer_sheet_types.{$this->type}", $this->type);
    }

    public function statusLabel(): string
    {
        return config("wowlo.answer_sheet_statuses.{$this->status}", $this->status);
    }

    /** Total possible marks across all rows. */
    public function totalMarks(): int
    {
        return (int) $this->questions->sum('marks');
    }

    public function percentage(): int
    {
        return $this->total_marks > 0
            ? (int) round($this->obtained_marks / $this->total_marks * 100)
            : 0;
    }
}
