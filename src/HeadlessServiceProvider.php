<?php

namespace Ssdev\Headless;

use Illuminate\Support\ServiceProvider;

class HeadlessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/headless.php', 'headless');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/headless.php' => config_path('headless.php'),
        ], 'headless-config');
    }
}
