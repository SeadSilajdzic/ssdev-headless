<?php

namespace Ssdev\Headless\Account;

enum ProfileUpdateOutcome
{
    case Saved;
    case EmailConfirmationSent;

    public function message(): string
    {
        return match ($this) {
            self::Saved => __('headless::settings.profile.saved'),
            self::EmailConfirmationSent => __('headless::settings.profile.email_confirmation_sent'),
        };
    }
}
