<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizQuestion extends Model
{
    protected $fillable = [
        'quiz_id', 'question_text', 'question_type', 'image_path', 'image_name',
        'option_a', 'option_b', 'option_c', 'option_d',
        'correct_answer', 'marks', 'order',
    ];

    protected $casts = [
        'marks' => 'integer',
        'order' => 'integer',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'question_id');
    }

    public function isShortAnswer(): bool
    {
        return $this->question_type === 'short_answer';
    }

    public function isMcq(): bool
    {
        return $this->question_type === 'mcq';
    }

    /**
     * The option text for a given letter (A/B/C/D).
     */
    public function optionText(string $letter): ?string
    {
        return $this->{'option_' . strtolower($letter)} ?? null;
    }

    public function hasImage(): bool
    {
        return ! empty($this->image_path);
    }

    /**
     * Whether the attachment is a previewable image (vs. a PDF/other).
     */
    public function imageIsPreviewable(): bool
    {
        $ext = strtolower(pathinfo((string) $this->image_name, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }
}
