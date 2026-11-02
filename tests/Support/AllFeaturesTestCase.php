<?php

namespace Ssdev\Headless\Tests\Support;

use Ssdev\Headless\Features\Feature;

abstract class AllFeaturesTestCase extends TenancyTestCase
{
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('headless.features', array_column(Feature::cases(), 'value'));
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations/teams');
    }
}
