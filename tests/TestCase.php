<?php

namespace Ssdev\Headless\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Ssdev\Headless\HeadlessServiceProvider;
use Workbench\App\Models\User;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function getPackageProviders($app): array
    {
        return [HeadlessServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
    }
}
