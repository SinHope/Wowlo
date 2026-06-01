<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection as BaseCollection;

class ExamPaper extends Model
{
    protected $fillable = [
        'tutor_id', 'level', 'title', 'subject', 'year',
        'file_path', 'original_filename', 'remarks',
    ];

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    /**
     * Arrange a flat collection of papers into Level → Subject → papers,
     * with levels and subjects in canonical config order and empty groups
     * dropped. Papers within a subject keep their incoming order (year desc).
     */
    public static function groupForDisplay(Collection $papers): BaseCollection
    {
        $byLevel = $papers->groupBy('level');

        return collect(config('wowlo.levels'))
            ->filter(fn ($level) => $byLevel->has($level))
            ->mapWithKeys(function ($level) use ($byLevel) {
                $bySubject = $byLevel->get($level)->groupBy('subject');

                $subjects = collect(config('wowlo.subjects'))
                    ->filter(fn ($subject) => $bySubject->has($subject))
                    ->mapWithKeys(fn ($subject) => [$subject => $bySubject->get($subject)]);

                return [$level => $subjects];
            });
    }
}
