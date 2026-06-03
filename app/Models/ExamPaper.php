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
        'status', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    /**
     * The super_admin who approved this paper (null while pending).
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /** @param \Illuminate\Database\Eloquent\Builder $query */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /** @param \Illuminate\Database\Eloquent\Builder $query */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
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
