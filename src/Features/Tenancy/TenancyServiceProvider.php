<?php

namespace Ssdev\Headless\Features\Tenancy;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Ssdev\Headless\Features\Tenancy\Http\Middleware\IdentifyTenant;
use Ssdev\Headless\Features\Tenancy\Tasks\ForceTenantRootUrl;

final class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Set before Spatie's provider boots, which reads these once.
        $this->app['config']->set([
            'multitenancy.tenant_model' => Tenant::class,
            // IdentifyTenant finds the tenant, so Spatie's own lookup while booting stays off.
            'multitenancy.tenant_finder' => null,
            'multitenancy.switch_tenant_tasks' => [ForceTenantRootUrl::class],
            'multitenancy.queues_are_tenant_aware_by_default' => true,
        ]);
    }

    public function boot(HttpKernel $kernel): void
    {
        // Global, so the application's own routes run in the tenant too; after TrustProxies, so a proxied host counts.
        if ($kernel instanceof Kernel) {
            $kernel->pushMiddleware(IdentifyTenant::class);
        }
    }
}
