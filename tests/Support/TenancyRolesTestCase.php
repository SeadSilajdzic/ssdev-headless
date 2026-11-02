<?php

namespace Ssdev\Headless\Tests\Support;

use Ssdev\Headless\Features\Feature;

abstract class TenancyRolesTestCase extends TenancyTestCase
{
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('headless.features', [Feature::Tenancy->value, Feature::Roles->value, Feature::Users->value]);
    }
}
