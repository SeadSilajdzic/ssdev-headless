<?php

namespace Ssdev\Headless\Features;

use Illuminate\Contracts\Foundation\Application;

final class FeatureBootstrapper
{
    public function __construct(private Application $app) {}

    public function registerProviders(FeatureRegistry $registry): void
    {
        foreach ($registry->enabled() as $module) {
            $this->app->register($module->serviceProvider());
        }
    }
}
