<?php

namespace Ssdev\Headless;

use Illuminate\Support\ServiceProvider;
use Ssdev\Headless\Features\FeatureBootstrapper;
use Ssdev\Headless\Features\FeatureCatalog;
use Ssdev\Headless\Features\FeatureRegistry;

class HeadlessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/headless.php', 'headless');

        $this->app->singleton(FeatureRegistry::class, fn ($app) => new FeatureRegistry(
            $app->make(FeatureCatalog::class)->modules(),
            $app['config']->get('headless.features', []),
        ));
    }

    public function boot(FeatureBootstrapper $bootstrapper, FeatureRegistry $registry): void
    {
        $bootstrapper->registerProviders($registry);

        $this->publishes([
            __DIR__.'/../config/headless.php' => config_path('headless.php'),
        ], 'headless-config');
    }
}
