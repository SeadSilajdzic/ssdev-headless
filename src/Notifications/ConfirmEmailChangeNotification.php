<?php

namespace Ssdev\Headless\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ConfirmEmailChangeNotification extends Notification
{
    public function __construct(public readonly string $url) {}

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
            ->subject(__('headless::notifications.confirm_email_change.subject'))
            ->line(__('headless::notifications.confirm_email_change.intro'))
            ->action(__('headless::notifications.confirm_email_change.action'), $this->url)
            ->line(__('headless::notifications.confirm_email_change.outro'));
    }
}
