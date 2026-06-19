<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnswerSheetQuestion extends Model
{
    protected $fillable = [
        'answer_sheet_id', 'order', 'num_options', 'marks',
        'choice', 'answer_text', 'grade', 'marks_awarded', 'tutor_feedback',
    ];

    protected function casts(): array
    {
        return [
            'order'         => 'integer',
            'num_options'   => 'integer',
            'marks'         => 'integer',
            'choice'        => 'integer',
            'marks_awarded' => 'float',
        ];
    }

    public function answerSheet(): BelongsTo
    {
        return $this->belongsTo(AnswerSheet::class);
    }
}
