<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    protected $fillable = [
        'quiz_id', 'student_id', 'total_marks', 'obtained_marks', 'feedback', 'completed_at', 'graded_at',
    ];

    protected $casts = [
        'total_marks'    => 'integer',
        'obtained_marks' => 'float', // can be fractional when short answers earn half-marks
        'completed_at'   => 'datetime',
        'graded_at'      => 'datetime',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'attempt_id');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Submitted, but the tutor still has short answers to mark. Legacy MCQ-only
     * attempts (graded_at NULL, no short-answer questions) are NOT "needs marking".
     */
    public function needsMarking(): bool
    {
        return $this->isCompleted()
            && $this->graded_at === null
            && $this->quiz->hasShortAnswers();
    }

    /**
     * Fully done from the student's perspective — results may be revealed.
     * MCQ-only quizzes are graded the instant they're submitted.
     */
    public function isGraded(): bool
    {
        return $this->isCompleted() && ! $this->needsMarking();
    }

    public function percentage(): int
    {
        return $this->total_marks > 0
            ? (int) round($this->obtained_marks / $this->total_marks * 100)
            : 0;
    }
}
