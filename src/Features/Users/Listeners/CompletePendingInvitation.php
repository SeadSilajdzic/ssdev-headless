<?php

namespace Ssdev\Headless\Features\Users\Listeners;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\User;

/**
 * Setting a password through the invitation or a normal reset both prove the email, so either one completes it.
 */
final class CompletePendingInvitation
{
    public function handle(PasswordReset $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || $user->getAttribute('invited_at') === null) {
            return;
        }

        $user->forceFill([
            'invited_at' => null,
            'email_verified_at' => $user->getAttribute('email_verified_at') ?? now(),
        ])->save();
    }
}
