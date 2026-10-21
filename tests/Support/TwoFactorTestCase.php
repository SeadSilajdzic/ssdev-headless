<?php

namespace Ssdev\Headless\Tests\Support;

use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Tests\TestCase;

abstract class TwoFactorTestCase extends TestCase
{
    // defineEnvironment() runs after the providers registered, too late for the feature list they read.
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('headless.features', [Feature::TwoFactor->value]);
    }
}
