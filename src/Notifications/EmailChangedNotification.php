<?php

namespace Ssdev\Headless\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class EmailChangedNotification extends Notification
{
    public function __construct(public readonly string $newEmail) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('headless::notifications.email_changed.subject'))
            ->line(__('headless::notifications.email_changed.intro', ['email' => $this->newEmail]))
            ->line(__('headless::notifications.email_changed.outro'));
    }
}
