<?php

namespace Ssdev\Headless\Features\Tenancy;

use Illuminate\Contracts\Auth\Access\Gate;

/**
 * Managing tenants belongs to the central level, so inside a tenant those permissions never count, however they were given.
 */
final class TenantPermissionGate
{
    public function __construct(private Gate $gate) {}

    public function register(): void
    {
        $this->gate->before(function (mixed $user, string $ability): ?bool {
            if (Tenant::current() === null || ! in_array($ability, TenantPermission::names(), true)) {
                return null;
            }

            return false;
        });
    }
}
