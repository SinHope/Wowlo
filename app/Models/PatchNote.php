<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Patch Note — one entry in the public changelog (Slice 16).
 *
 * Everyone reads; only the super_admin writes. `body` is sanitised allowlist
 * HTML (see App\Support\HtmlSanitizer); the optional image lives on R2 and is
 * referenced by object key only.
 */
class PatchNote extends Model
{
    protected $fillable = [
        'title',
        'version',
        'body',
        'image_path',
        'image_name',
        'created_by',
    ];

    /** The super_admin who wrote this note. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Does this note have an attached image? */
    public function hasImage(): bool
    {
        return filled($this->image_path);
    }
}
