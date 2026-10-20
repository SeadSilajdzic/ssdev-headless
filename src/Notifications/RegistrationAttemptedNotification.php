<?php

namespace Ssdev\Headless\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RegistrationAttemptedNotification extends Notification
{
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
            ->subject(__('headless::notifications.registration_attempted.subject'))
            ->line(__('headless::notifications.registration_attempted.intro'))
            ->action(__('headless::notifications.registration_attempted.action'), route('login'))
            ->line(__('headless::notifications.registration_attempted.outro'));
    }
}
