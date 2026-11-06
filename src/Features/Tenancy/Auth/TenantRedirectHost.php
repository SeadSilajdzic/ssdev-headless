<?php

namespace Ssdev\Headless\Features\Tenancy\Auth;

use Ssdev\Headless\Auth\RedirectHost;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Ssdev\Headless\Features\Tenancy\TenantHosts;

/**
 * Each level has its own sign-ins, so an intended URL never leaves the tenant or the central domain it was asked for on.
 */
final class TenantRedirectHost implements RedirectHost
{
    public function __construct(private TenantHosts $hosts) {}

    public function current(): string
    {
        $tenant = Tenant::current();

        return $tenant === null ? $this->hosts->centralDomain() : $this->hosts->hostOf($tenant);
    }
}
