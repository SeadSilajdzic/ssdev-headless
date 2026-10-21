<?php

namespace Ssdev\Headless\Features\TwoFactor;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Auth\User;

final class TwoFactorRequirement
{
    public function __construct(private Repository $config) {}

    public function appliesTo(User $user): bool
    {
        if ($this->config->get('headless.two_factor.required')) {
            return true;
        }

        foreach ((array) $this->config->get('headless.two_factor.required_for_permissions', []) as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
