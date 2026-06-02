<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NewMessageNotification extends Notification
{
    use Queueable;

    public function __construct(public Message $message) {}

    /**
     * Deliver only over web push (MVP — no mail/db channel).
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('New message: ' . Str::limit($this->message->subject, 50))
            ->icon('/images/pwa/icon-192.png')
            ->body(Str::limit($this->message->body, 80))
            ->data(['url' => route('student.messages.show', $this->message, false)]);
    }
}
