<?php

namespace Ssdev\Headless\Api;

use Illuminate\Contracts\Auth\Access\Gate;
use Laravel\Sanctum\PersonalAccessToken;
use Ssdev\Headless\Permissions\PermissionRegistry;

/**
 * A token may use a permission only when it was given that permission and its owner still has it.
 * Returning null leaves the owner's own permissions to decide.
 */
final class TokenAbilityGate
{
    public function __construct(
        private Gate $gate,
        private PermissionRegistry $permissions,
    ) {}

    public function register(): void
    {
        $this->gate->before(function (mixed $user, string $ability): ?bool {
            $token = is_object($user) && method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;

            if (! $token instanceof PersonalAccessToken || ! in_array($ability, $this->permissions->names(), true)) {
                return null;
            }

            return $token->can($ability) ? null : false;
        });
    }
}
