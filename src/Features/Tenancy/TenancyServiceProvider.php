<?php

namespace Ssdev\Headless\Features\Tenancy;

use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\ServiceProvider;
use Ssdev\Headless\Features\Tenancy\Auth\TenantPasswordBrokerManager;
use Ssdev\Headless\Features\Tenancy\Http\Middleware\EnsureSessionMatchesTenant;
use Ssdev\Headless\Features\Tenancy\Http\Middleware\IdentifyTenant;
use Ssdev\Headless\Features\Tenancy\Tasks\ForceTenantRootUrl;
use Ssdev\Headless\Features\Tenancy\Tasks\SwitchTenantRegistration;

final class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Set before Spatie's provider boots, which reads these once.
        $this->app['config']->set([
            'multitenancy.tenant_model' => Tenant::class,
            // IdentifyTenant finds the tenant, so Spatie's own lookup while booting stays off.
            'multitenancy.tenant_finder' => null,
            'multitenancy.switch_tenant_tasks' => [ForceTenantRootUrl::class, SwitchTenantRegistration::class],
            'multitenancy.queues_are_tenant_aware_by_default' => true,
        ]);

        // Extended rather than rebound: Laravel's provider is deferred and would replace a plain binding when it loads.
        $this->app->extend('auth.password', fn (PasswordBrokerManager $manager, Application $app) => new TenantPasswordBrokerManager($app));
    }

    public function boot(HttpKernel $kernel): void
    {
        // Global, so the application's own routes run in the tenant too; after TrustProxies, so a proxied host counts.
        if ($kernel instanceof Kernel) {
            $kernel->pushMiddleware(IdentifyTenant::class);
            $kernel->appendMiddlewareToGroup('web', EnsureSessionMatchesTenant::class);
            // Right after the session starts, so routes that authenticate never see a sign-in from another tenant.
            $kernel->addToMiddlewarePriorityAfter(StartSession::class, EnsureSessionMatchesTenant::class);
        }
    }
}
