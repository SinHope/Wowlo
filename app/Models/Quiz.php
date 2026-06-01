<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    protected $fillable = [
        'tutor_id', 'title', 'subject', 'level', 'topic', 'exam_type',
    ];

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(QuizAssignment::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Total possible marks across all questions.
     */
    public function totalMarks(): int
    {
        return (int) $this->questions->sum('marks');
    }

    /**
     * Human-readable exam-type label from config.
     */
    public function examTypeLabel(): string
    {
        return config("wowlo.exam_types.{$this->exam_type}", $this->exam_type);
    }
}
