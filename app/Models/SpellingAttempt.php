<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpellingAttempt extends Model
{
    protected $fillable = [
        'student_id', 'tutor_id', 'level',
        'total_questions', 'correct_count', 'score_percent',
        'results', 'reflection', 'feedback', 'feedback_by', 'feedback_at',
    ];

    protected function casts(): array
    {
        return [
            'results'         => 'array',
            'total_questions' => 'integer',
            'correct_count'   => 'integer',
            'score_percent'   => 'integer',
            'feedback_at'     => 'datetime',
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

    public function feedbackBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'feedback_by');
    }

    /** Has the student written their (required) reflection yet? */
    public function hasReflection(): bool
    {
        return filled($this->reflection);
    }

    /** Has the tutor left feedback yet? */
    public function hasFeedback(): bool
    {
        return filled($this->feedback);
    }
}
