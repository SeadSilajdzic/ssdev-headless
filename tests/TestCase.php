<?php

namespace Ssdev\Headless\Tests;

use Illuminate\Contracts\Http\Kernel;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Laravel\Fortify\FortifyServiceProvider;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Permission\PermissionServiceProvider;
use Ssdev\Headless\HeadlessServiceProvider;
use Ssdev\Headless\Http\Middleware\HandleHeadlessInertiaRequests;
use Workbench\App\Models\User;

abstract class TestCase extends Orchestra
{
    use WithLaravelMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->app->make(Kernel::class)->appendMiddlewareToGroup('web', HandleHeadlessInertiaRequests::class);
    }

    protected function getPackageProviders($app): array
    {
        return [InertiaServiceProvider::class, FortifyServiceProvider::class, PermissionServiceProvider::class, HeadlessServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../workbench/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }
}
