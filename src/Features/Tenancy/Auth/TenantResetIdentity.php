<?php

namespace Ssdev\Headless\Features\Tenancy\Auth;

use Illuminate\Contracts\Auth\CanResetPassword;
use Ssdev\Headless\Features\Tenancy\TenantContext;

/**
 * The key a reset token is stored under: the same email in two tenants belongs to two accounts.
 */
final class TenantResetIdentity implements CanResetPassword
{
    private const EMAIL_HASH = 'sha256';

    public function __construct(
        private CanResetPassword $user,
        private int $tenantKey,
    ) {}

    public function getEmailForPasswordReset(): string
    {
        $email = (string) $this->user->getEmailForPasswordReset();

        // A tenant's key carries a hash, never an "@", so no central account's plain email can match it.
        return $this->tenantKey === TenantContext::CENTRAL ? $email : $this->tenantKey.'|'.hash(self::EMAIL_HASH, $email);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->user->sendPasswordResetNotification($token);
    }
}
