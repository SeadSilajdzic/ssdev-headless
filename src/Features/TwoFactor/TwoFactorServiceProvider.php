<?php

namespace Ssdev\Headless\Features\TwoFactor;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Features;

final class TwoFactorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app['config']->push('fortify.features', Features::twoFactorAuthentication(['confirm' => true]));
    }
}
