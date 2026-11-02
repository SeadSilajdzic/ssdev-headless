<?php

namespace Ssdev\Headless\Tests\Support;

use Ssdev\Headless\Features\Feature;

abstract class TenancyTeamsUsersTestCase extends TenancyTestCase
{
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('headless.features', [Feature::Tenancy->value, Feature::Teams->value, Feature::Users->value]);
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations/teams');
    }
}
