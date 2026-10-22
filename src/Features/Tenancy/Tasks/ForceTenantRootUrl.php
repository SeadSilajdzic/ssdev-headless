<?php

namespace Ssdev\Headless\Features\Tenancy\Tasks;

use Illuminate\Support\Facades\URL;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Ssdev\Headless\Features\Tenancy\TenantHosts;

/**
 * Links built in the console or a queued job point at the tenant's own host.
 */
final class ForceTenantRootUrl implements SwitchTenantTask
{
    public function __construct(private TenantHosts $hosts) {}

    public function makeCurrent(IsTenant $tenant): void
    {
        if ($tenant instanceof Tenant) {
            URL::forceRootUrl($this->hosts->rootUrlFor($tenant));
        }
    }

    public function forgetCurrent(): void
    {
        URL::forceRootUrl(null);
    }
}
