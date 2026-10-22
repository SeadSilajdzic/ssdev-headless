<?php

namespace Ssdev\Headless\Tests\Support;

use Spatie\Multitenancy\MultitenancyServiceProvider;
use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Tests\TestCase;
use Workbench\App\Models\TenantUser;

abstract class TenancyTestCase extends TestCase
{
    // defineEnvironment() runs after the providers registered, too late for the feature list they read.
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('headless.features', [Feature::Tenancy->value]);
        $app['config']->set('headless.tenancy.base_domain', 'localhost');
        $app['config']->set('headless.tenancy.central_domain', 'localhost');
    }

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), MultitenancyServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations/tenancy');
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('auth.providers.users.model', TenantUser::class);
    }
}
