<?php

namespace Ssdev\Headless\Features\Tenancy\Tasks;

use Illuminate\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use Illuminate\Routing\UrlGenerator;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Ssdev\Headless\Features\Tenancy\TenantHosts;

/**
 * Links built in the console or a queued job point at the tenant's own host.
 */
final class ForceTenantRootUrl implements SwitchTenantTask
{
    private ?string $previousRoot = null;

    public function __construct(
        private TenantHosts $hosts,
        private UrlGeneratorContract $urls,
    ) {}

    public function makeCurrent(IsTenant $tenant): void
    {
        if (! $tenant instanceof Tenant || ! $this->urls instanceof UrlGenerator) {
            return;
        }

        // Laravel has no getter for a forced root, and the application may have forced one of its own (workers, Octane).
        $this->previousRoot = (fn (): ?string => $this->forcedRoot)->call($this->urls);
        $this->urls->forceRootUrl($this->hosts->rootUrlFor($tenant));
    }

    public function forgetCurrent(): void
    {
        if ($this->urls instanceof UrlGenerator) {
            $this->urls->forceRootUrl($this->previousRoot);
        }

        $this->previousRoot = null;
    }
}
