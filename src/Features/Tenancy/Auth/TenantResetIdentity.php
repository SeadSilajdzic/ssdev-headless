<?php

namespace Ssdev\Headless\Features\Tenancy\Auth;

use Illuminate\Contracts\Auth\CanResetPassword;
use Ssdev\Headless\Features\Tenancy\TenantContext;

/**
 * The key a reset token is stored under: the same email in two tenants belongs to two accounts.
 */
final class TenantResetIdentity implements CanResetPassword
{
    public function __construct(
        private CanResetPassword $user,
        private int $tenantKey,
    ) {}

    public function getEmailForPasswordReset(): string
    {
        $email = (string) $this->user->getEmailForPasswordReset();

        return $this->tenantKey === TenantContext::CENTRAL ? $email : $this->tenantKey.'|'.$email;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->user->sendPasswordResetNotification($token);
    }
}
