<?php

namespace Ssdev\Headless\Features\Users\Notifications;

use Carbon\CarbonInterval;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class UserInvitationNotification extends Notification
{
    public function __construct(public readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(CanResetPassword $notifiable): MailMessage
    {
        $app = (string) config('app.name');
        $validFor = CarbonInterval::minutes((int) config('headless.users.invitation_expire_minutes'))->cascade()->forHumans();

        return (new MailMessage)
            ->subject(__('headless::notifications.user_invitation.subject', ['app' => $app]))
            ->line(__('headless::notifications.user_invitation.intro', ['app' => $app]))
            ->action(__('headless::notifications.user_invitation.action'), route('headless.invitation.show', [
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]))
            ->line(__('headless::notifications.user_invitation.outro', ['time' => $validFor]));
    }
}
