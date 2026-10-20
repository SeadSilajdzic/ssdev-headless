<?php

namespace Ssdev\Headless;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Ssdev\Headless\Console\InstallCommand;
use Ssdev\Headless\Features\FeatureBootstrapper;
use Ssdev\Headless\Features\FeatureCatalog;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Installation\ProjectFiles;
use Ssdev\Headless\Navigation\NavigationGroup;
use Ssdev\Headless\Navigation\NavigationItem;
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

        $this->app->bind(ProjectFiles::class, fn ($app) => new ProjectFiles($app->basePath(), $app->make(Filesystem::class)));
    }

    public function boot(FeatureBootstrapper $bootstrapper, FeatureRegistry $registry, NavigationRegistry $navigation): void
    {
        $bootstrapper->registerProviders($registry);

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'headless');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'headless');
        $this->registerRoutes();

        $navigation->add(new NavigationItem(
            'dashboard',
            'headless::navigation.dashboard',
            'headless.dashboard',
            'layout-dashboard',
            NavigationGroup::Main,
        ));

        $this->publishes([
            __DIR__.'/../config/headless.php' => config_path('headless.php'),
        ], 'headless-config');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/headless'),
        ], 'headless-lang');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/headless'),
        ], 'headless-views');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);
        }
    }

    private function registerRoutes(): void
    {
        Route::middleware(config('headless.routes.middleware'))
            ->prefix(config('headless.routes.prefix'))
            ->group(__DIR__.'/../routes/web.php');
    }
}
