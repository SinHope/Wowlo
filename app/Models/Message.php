<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'subject',
        'body',
        'is_read',
    ];

    // New messages start unread even before the row is reloaded from the DB.
    protected $attributes = [
        'is_read' => false,
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Prefilled subject for a reply: "Re: …" without stacking "Re: Re:".
     */
    public function replySubject(): string
    {
        $subject = Str::startsWith($this->subject, 'Re: ') ? $this->subject : 'Re: ' . $this->subject;

        return Str::limit($subject, 255, '');
    }
}
