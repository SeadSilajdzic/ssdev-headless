<?php

namespace Ssdev\Headless\Features\Tenancy\Notifications;

use Carbon\CarbonInterval;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The link is built while the tenant is current, so it points at the tenant's own host.
 */
final class TenantOwnerInvitation extends Notification
{
    public function __construct(
        public readonly string $tenantName,
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
        $validFor = CarbonInterval::minutes((int) config('headless.tenancy.owner_invitation_expire_minutes'))->cascade()->forHumans();

        return (new MailMessage)
            ->subject(__('headless::notifications.tenant_owner_invitation.subject', ['tenant' => $this->tenantName]))
            ->line(__('headless::notifications.tenant_owner_invitation.intro', ['tenant' => $this->tenantName]))
            ->action(__('headless::notifications.tenant_owner_invitation.action'), $this->url)
            ->line(__('headless::notifications.tenant_owner_invitation.outro', ['time' => $validFor]));
    }
}
