<?php

namespace Ssdev\Headless\Features\Tenancy;

use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\ServiceProvider;
use Spatie\Multitenancy\Tasks\PrefixCacheTask;
use Ssdev\Headless\Features\Tenancy\Auth\TenantPasswordBrokerManager;
use Ssdev\Headless\Features\Tenancy\Http\Middleware\EnsureCentralLevel;
use Ssdev\Headless\Features\Tenancy\Http\Middleware\EnsureSessionMatchesTenant;
use Ssdev\Headless\Features\Tenancy\Http\Middleware\EnsureTenantIsActive;
use Ssdev\Headless\Features\Tenancy\Http\Middleware\IdentifyTenant;
use Ssdev\Headless\Features\Tenancy\Queue\KeepQueuedJobsInTheirTenantAction;
use Ssdev\Headless\Features\Tenancy\Tasks\ForceTenantRootUrl;
use Ssdev\Headless\Features\Tenancy\Tasks\SwitchTenantRegistration;
use Ssdev\Headless\Navigation\NavigationGroup;
use Ssdev\Headless\Navigation\NavigationItem;
use Ssdev\Headless\Navigation\NavigationRegistry;
use Ssdev\Headless\Routing\PackageRoutes;
use Ssdev\Headless\Support\Isolation;

final class TenancyServiceProvider extends ServiceProvider
{
    private const OWNER_INVITATION_THROTTLE_SECONDS = 60;

    public function register(): void
    {
        $config = $this->app['config'];

        // A table of their own, so a reset token never counts as an owner's invitation that lives a week.
        $config->set('auth.passwords.'.TenantOwnerService::BROKER, [
            'provider' => $config->get('auth.passwords.users.provider', 'users'),
            'table' => TenancyModule::OWNER_TOKENS_TABLE,
            'expire' => (int) $config->get('headless.tenancy.owner_invitation_expire_minutes'),
            'throttle' => self::OWNER_INVITATION_THROTTLE_SECONDS,
        ]);

        // Set before Spatie's provider boots, which reads these once.
        $config->set([
            'multitenancy.tenant_model' => Tenant::class,
            // IdentifyTenant finds the tenant, so Spatie's own lookup while booting stays off.
            'multitenancy.tenant_finder' => null,
            // The rate limiter keeps the cache store it started with, so its keys are kept apart through TenantIsolation instead.
            'multitenancy.switch_tenant_tasks' => [PrefixCacheTask::class, ForceTenantRootUrl::class, SwitchTenantRegistration::class],
            'multitenancy.queues_are_tenant_aware_by_default' => true,
            'multitenancy.actions.make_queue_tenant_aware_action' => KeepQueuedJobsInTheirTenantAction::class,
            // The central level has its own administrators; SwitchTenantRegistration opens sign-up per tenant.
            'headless.registration.enabled' => false,
        ]);

        // On booting, so it follows the token ability gate and precedes Spatie's, which registers while booting.
        $this->app->booting(fn () => $this->app->make(TenantPermissionGate::class)->register());

        $this->app->bind(Isolation::class, TenantIsolation::class);

        // Extended rather than rebound: Laravel's provider is deferred and would replace a plain binding when it loads.
        $this->app->extend('auth.password', fn (PasswordBrokerManager $manager, Application $app) => new TenantPasswordBrokerManager($app));
    }

    public function boot(HttpKernel $kernel, OwnerInvitationRateLimit $rateLimit, NavigationRegistry $navigation): void
    {
        $rateLimit->register();

        // Global, so the application's own routes run in the tenant too; after TrustProxies, so a proxied host counts.
        if ($kernel instanceof Kernel) {
            $kernel->pushMiddleware(IdentifyTenant::class);
            $kernel->appendMiddlewareToGroup('web', EnsureSessionMatchesTenant::class);
            $kernel->appendMiddlewareToGroup('web', EnsureTenantIsActive::class);
            $kernel->appendMiddlewareToGroup('api', EnsureTenantIsActive::class.':'.EnsureTenantIsActive::JSON);
            // Right after the session starts, so routes that authenticate never see a sign-in from another tenant or a suspended one.
            $kernel->addToMiddlewarePriorityAfter(StartSession::class, EnsureSessionMatchesTenant::class);
            $kernel->addToMiddlewarePriorityAfter(EnsureSessionMatchesTenant::class, EnsureTenantIsActive::class);
            // Before authentication, so the central panel answers 404 on a tenant's host instead of asking for a sign-in.
            $kernel->addToMiddlewarePriorityAfter(EnsureTenantIsActive::class, EnsureCentralLevel::class);
        }

        PackageRoutes::web(__DIR__.'/../../../routes/tenant-owner.php');
        PackageRoutes::web(__DIR__.'/../../../routes/tenants.php');

        $navigation->add(new NavigationItem(
            'tenants',
            'headless::navigation.tenants',
            'headless.tenants.index',
            'building-2',
            NavigationGroup::Administration,
            permission: TenantPermission::View->value,
        ));
    }
}
