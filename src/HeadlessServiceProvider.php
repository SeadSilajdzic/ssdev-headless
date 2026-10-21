<?php

namespace Ssdev\Headless;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Ssdev\Headless\Api\ApiRoutes;
use Ssdev\Headless\Api\TokenAbilityGate;
use Ssdev\Headless\Auth\FortifyIntegration;
use Ssdev\Headless\Auth\RateLimits;
use Ssdev\Headless\Console\CreateAdminCommand;
use Ssdev\Headless\Console\GrantPermissionsCommand;
use Ssdev\Headless\Console\InstallCommand;
use Ssdev\Headless\Console\PublishCommand;
use Ssdev\Headless\Console\SyncPermissionsCommand;
use Ssdev\Headless\Console\UpgradeCommand;
use Ssdev\Headless\Features\FeatureBootstrapper;
use Ssdev\Headless\Features\FeatureCatalog;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Installation\ProjectFiles;
use Ssdev\Headless\Navigation\NavigationGroup;
use Ssdev\Headless\Navigation\NavigationItem;
use Ssdev\Headless\Navigation\NavigationRegistry;
use Ssdev\Headless\Permissions\PermissionRegistry;
use Ssdev\Headless\Translations\HeadlessTranslations;

class HeadlessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/headless.php', 'headless');

        $this->app->singleton(FortifyIntegration::class);
        $this->app->make(FortifyIntegration::class)->register();

        // Gate::before callbacks run in order and Spatie's says yes on its own, so this one must come first.
        $this->app->booting(fn () => $this->app->make(TokenAbilityGate::class)->register());

        $this->app->singleton(FeatureRegistry::class, fn ($app) => new FeatureRegistry(
            $app->make(FeatureCatalog::class)->modules(),
            $app['config']->get('headless.features', []),
        ));

        $this->app->singleton(HeadlessTranslations::class, fn ($app) => new HeadlessTranslations(
            $app['translator'],
            __DIR__.'/../lang',
        ));

        $this->app->singleton(NavigationRegistry::class);

        $this->app->singleton(PermissionRegistry::class, fn ($app) => new PermissionRegistry($app['config']->get('headless.permissions', [])));

        $this->app->bind(ProjectFiles::class, fn ($app) => new ProjectFiles($app->basePath(), $app->make(Filesystem::class)));
    }

    public function boot(FeatureBootstrapper $bootstrapper, FeatureRegistry $registry, NavigationRegistry $navigation, FortifyIntegration $fortify, RateLimits $rateLimits): void
    {
        $fortify->boot();
        $rateLimits->register();
        RedirectIfAuthenticated::redirectUsing(fn () => route('headless.dashboard'));
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

        $navigation->add(new NavigationItem(
            'profile',
            'headless::navigation.profile',
            'headless.profile.edit',
            'user',
            NavigationGroup::Account,
        ));

        $navigation->add(new NavigationItem(
            'security',
            'headless::navigation.security',
            'headless.security.edit',
            'shield',
            NavigationGroup::Account,
            order: 10,
        ));

        if (config('headless.api.enabled')) {
            $navigation->add(new NavigationItem(
                'api-tokens',
                'headless::navigation.api_tokens',
                'headless.api-tokens.index',
                'key',
                NavigationGroup::Account,
                order: 20,
            ));
        }

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
            $this->commands([InstallCommand::class, PublishCommand::class, SyncPermissionsCommand::class, GrantPermissionsCommand::class, UpgradeCommand::class, CreateAdminCommand::class]);
        }
    }

    private function registerRoutes(): void
    {
        Route::middleware(config('headless.routes.middleware'))
            ->prefix(config('headless.routes.prefix'))
            ->group(function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
                $this->loadRoutesFrom(__DIR__.'/../routes/auth.php');
                $this->loadRoutesFrom(__DIR__.'/../routes/settings.php');
            });

        Route::middleware(ApiRoutes::MIDDLEWARE)
            ->prefix(config('headless.api.prefix'))
            ->group(fn () => $this->loadRoutesFrom(__DIR__.'/../routes/api.php'));
    }
}
