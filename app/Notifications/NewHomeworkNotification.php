<?php

namespace App\Notifications;

use App\Models\Homework;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NewHomeworkNotification extends Notification
{
    use Queueable;

    public function __construct(public Homework $homework) {}

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
            ->title('New homework: ' . $this->homework->subject)
            ->icon('/images/pwa/icon-192.png')
            ->body(Str::limit($this->homework->title, 80))
            ->data(['url' => route('student.homework.show', $this->homework, false)]);
    }
}
