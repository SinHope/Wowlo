<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Banner Notification — a super_admin-only, app-wide announcement bar.
 *
 * Shown at the top of the app to a chosen audience (everyone / tutors /
 * students). `content` is a small allowlist of sanitised rich-text HTML
 * (see App\Support\HtmlSanitizer) — never raw user HTML.
 */
class Banner extends Model
{
    protected $fillable = [
        'content',
        'audience',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** The super_admin who composed this banner. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Only banners currently switched on. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * The active banners a given user should see right now, newest first.
     * A 'students' banner is hidden from tutors and the super_admin, and a
     * 'tutors' banner is hidden from students; 'everyone' shows to all.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $audiences = $user->isStudent() ? ['everyone', 'students'] : ['everyone', 'tutors'];

        return $query->active()
            ->whereIn('audience', $audiences)
            ->latest();
    }
}
