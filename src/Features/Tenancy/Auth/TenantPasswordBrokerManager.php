<?php

namespace Ssdev\Headless\Features\Tenancy\Auth;

use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;

final class TenantPasswordBrokerManager extends PasswordBrokerManager
{
    /**
     * @param  array<string, mixed>  $config
     */
    protected function createTokenRepository(array $config): TokenRepositoryInterface
    {
        return new TenantPasswordResetTokens(parent::createTokenRepository($config));
    }
}
