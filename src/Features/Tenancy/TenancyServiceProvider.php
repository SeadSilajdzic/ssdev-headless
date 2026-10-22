<?php

namespace Ssdev\Headless\Features\Tenancy;

use Illuminate\Support\ServiceProvider;

final class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Set before Spatie's provider boots, which reads these once.
        $this->app['config']->set([
            'multitenancy.tenant_model' => Tenant::class,
            // IdentifyTenant finds the tenant, so Spatie's own lookup while booting stays off.
            'multitenancy.tenant_finder' => null,
            'multitenancy.switch_tenant_tasks' => [],
            'multitenancy.queues_are_tenant_aware_by_default' => true,
        ]);
    }
}
