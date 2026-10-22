<?php

namespace Ssdev\Headless\Features\Tenancy\Auth;

use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use Illuminate\Contracts\Auth\CanResetPassword;
use Ssdev\Headless\Features\Tenancy\TenantContext;

/**
 * Wraps whichever token store the application configured and keys every token by tenant.
 */
final class TenantPasswordResetTokens implements TokenRepositoryInterface
{
    public function __construct(private TokenRepositoryInterface $tokens) {}

    public function create(CanResetPassword $user): string
    {
        return $this->tokens->create($this->identity($user));
    }

    public function exists(CanResetPassword $user, #[\SensitiveParameter] $token): bool
    {
        return $this->tokens->exists($this->identity($user), $token);
    }

    public function recentlyCreatedToken(CanResetPassword $user): bool
    {
        return $this->tokens->recentlyCreatedToken($this->identity($user));
    }

    public function delete(CanResetPassword $user): void
    {
        $this->tokens->delete($this->identity($user));
    }

    public function deleteExpired(): void
    {
        $this->tokens->deleteExpired();
    }

    private function identity(CanResetPassword $user): TenantResetIdentity
    {
        return new TenantResetIdentity($user, TenantContext::currentKey());
    }
}
