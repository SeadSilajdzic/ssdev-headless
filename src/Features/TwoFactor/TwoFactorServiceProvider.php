<?php

namespace Ssdev\Headless\Features\TwoFactor;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Features;
use Ssdev\Headless\Navigation\NavigationGroup;
use Ssdev\Headless\Navigation\NavigationItem;
use Ssdev\Headless\Navigation\NavigationRegistry;
use Ssdev\Headless\Routing\PackageRoutes;

final class TwoFactorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app['config']->push('fortify.features', Features::twoFactorAuthentication(['confirm' => true]));
    }

    public function boot(TwoFactorRateLimit $rateLimit, NavigationRegistry $navigation): void
    {
        $rateLimit->register();

        PackageRoutes::web(__DIR__.'/../../../routes/two-factor.php');

        $navigation->add(new NavigationItem(
            'two-factor',
            'headless::navigation.two_factor',
            'headless.two-factor.edit',
            'smartphone',
            NavigationGroup::Account,
            order: 15,
        ));
    }
}
