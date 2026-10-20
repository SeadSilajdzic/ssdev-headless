<?php

namespace Ssdev\Headless;

use Illuminate\Support\ServiceProvider;
use Ssdev\Headless\Features\FeatureBootstrapper;
use Ssdev\Headless\Features\FeatureCatalog;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Navigation\NavigationRegistry;
use Ssdev\Headless\Translations\HeadlessTranslations;

class HeadlessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/headless.php', 'headless');

        $this->app->singleton(FeatureRegistry::class, fn ($app) => new FeatureRegistry(
            $app->make(FeatureCatalog::class)->modules(),
            $app['config']->get('headless.features', []),
        ));

        $this->app->singleton(HeadlessTranslations::class, fn ($app) => new HeadlessTranslations(
            $app['translator'],
            __DIR__.'/../lang',
        ));

        $this->app->singleton(NavigationRegistry::class);
    }

    public function boot(FeatureBootstrapper $bootstrapper, FeatureRegistry $registry): void
    {
        $bootstrapper->registerProviders($registry);

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'headless');

        $this->publishes([
            __DIR__.'/../config/headless.php' => config_path('headless.php'),
        ], 'headless-config');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/headless'),
        ], 'headless-lang');
    }
}
