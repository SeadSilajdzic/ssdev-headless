<?php

namespace Ssdev\Headless\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AccountCreatedNotification extends Notification
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
            ->subject(__('headless::notifications.account_created.subject'))
            ->line(__('headless::notifications.account_created.intro'))
            ->action(__('headless::notifications.account_created.action'), route('login'));
    }
}
