<?php

namespace Ssdev\Headless\Features\TwoFactor;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Inertia\Response;
use Laravel\Fortify\Contracts\FailedTwoFactorLoginResponse as FailedTwoFactorLoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Ssdev\Headless\Auth\ApiSignInChallenge;
use Ssdev\Headless\Auth\Responses\LoginResponse;
use Ssdev\Headless\Features\TwoFactor\Http\Middleware\RequireTwoFactorAuthentication;
use Ssdev\Headless\Features\TwoFactor\Http\Responses\FailedTwoFactorLoginResponse;
use Ssdev\Headless\Http\Inertia\HeadlessPage;
use Ssdev\Headless\Navigation\NavigationGroup;
use Ssdev\Headless\Navigation\NavigationItem;
use Ssdev\Headless\Navigation\NavigationRegistry;
use Ssdev\Headless\Routing\PackageRoutes;

final class TwoFactorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app['config']->push('fortify.features', Features::twoFactorAuthentication(['confirm' => true]));

        $this->app->singleton(TwoFactorLoginResponse::class, LoginResponse::class);
        $this->app->singleton(FailedTwoFactorLoginResponseContract::class, FailedTwoFactorLoginResponse::class);
        $this->app->bind(ApiSignInChallenge::class, TwoFactorApiSignInChallenge::class);
    }

    public function boot(TwoFactorRateLimit $rateLimit, NavigationRegistry $navigation, HttpKernel $kernel): void
    {
        $rateLimit->register();

        // Added to the kernel rather than the router, which the kernel overwrites whenever its own groups change.
        if ($kernel instanceof Kernel) {
            $kernel->appendMiddlewareToGroup('web', RequireTwoFactorAuthentication::class);
            $kernel->appendMiddlewareToGroup('api', RequireTwoFactorAuthentication::class.':sanctum');
        }

        PackageRoutes::web(__DIR__.'/../../../routes/two-factor.php');
        PackageRoutes::api(__DIR__.'/../../../routes/two-factor-api.php');

        Fortify::twoFactorChallengeView(fn (): Response => HeadlessPage::render('TwoFactor/Challenge', [
            'submitUrl' => route('two-factor.login.store'),
            'loginUrl' => route('login'),
        ]));

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
