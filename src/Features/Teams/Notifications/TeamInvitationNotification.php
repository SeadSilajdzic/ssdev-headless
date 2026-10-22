<?php

namespace Ssdev\Headless\Features\Teams\Notifications;

use Carbon\CarbonInterval;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TeamInvitationNotification extends Notification
{
    public function __construct(
        public readonly string $teamName,
        public readonly string $url,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $validFor = CarbonInterval::minutes((int) config('headless.teams.invitation_expire_minutes'))->cascade()->forHumans();

        return (new MailMessage)
            ->subject(__('headless::notifications.team_invitation.subject', ['team' => $this->teamName]))
            ->line(__('headless::notifications.team_invitation.intro', ['team' => $this->teamName, 'app' => (string) config('app.name')]))
            ->action(__('headless::notifications.team_invitation.action'), $this->url)
            ->line(__('headless::notifications.team_invitation.outro', ['time' => $validFor]));
    }
}
