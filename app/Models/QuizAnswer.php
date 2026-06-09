<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAnswer extends Model
{
    protected $fillable = [
        'attempt_id', 'question_id', 'student_answer',
        'is_correct', 'marks_awarded', 'grade', 'ai_feedback', 'correction',
        'tutor_feedback', 'tutor_feedback_image_path', 'tutor_feedback_image_name',
    ];

    protected $casts = [
        'is_correct'    => 'boolean',
        'marks_awarded' => 'float', // supports half-marks (e.g. 0.5)
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'question_id');
    }

    public function hasTutorFeedbackImage(): bool
    {
        return ! empty($this->tutor_feedback_image_path);
    }

    /**
     * Human label + colour key for the manual grade (short answers).
     */
    public function gradeLabel(): ?string
    {
        return match ($this->grade) {
            'correct' => 'Correct',
            'partial' => 'Partially correct',
            'wrong'   => 'Wrong',
            default   => null,
        };
    }

    /**
     * Whether this answer still counts as "needs a correction" — any wrong or
     * partial answer (MCQ or graded short answer).
     */
    public function needsCorrection(): bool
    {
        if ($this->grade !== null) {
            return in_array($this->grade, ['partial', 'wrong'], true);
        }

        return ! $this->is_correct;
    }
}
