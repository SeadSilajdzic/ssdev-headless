<?php

namespace Ssdev\Headless\Auth;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Response;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Fortify;
use Ssdev\Headless\Auth\Responses\LoginResponse;
use Ssdev\Headless\Auth\Responses\LogoutResponse;
use Ssdev\Headless\Http\Inertia\HeadlessPage;
use Ssdev\Headless\Support\Flash;

final class FortifyIntegration
{
    public function __construct(
        private Container $container,
        private Repository $config,
    ) {}

    public function register(): void
    {
        // The package registers Fortify's controllers itself, with its own prefix and middleware.
        Fortify::ignoreRoutes();

        $this->config->set([
            'fortify.guard' => 'web',
            'fortify.passwords' => 'users',
            'fortify.username' => 'email',
            'fortify.email' => 'email',
            'fortify.lowercase_usernames' => true,
            'fortify.features' => [],
            // With a named limiter Fortify skips its own hard-coded login throttle.
            'fortify.limiters.login' => RateLimits::LOGIN,
        ]);

        $this->container->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->container->singleton(LogoutResponseContract::class, LogoutResponse::class);
    }

    public function boot(): void
    {
        Fortify::loginView(fn (Request $request): Response => HeadlessPage::render('Auth/Login', [
            'loginUrl' => route('login.store'),
            'passwordRequestUrl' => $this->routeUrl('password.request'),
            'registerUrl' => $this->config->get('headless.registration.enabled') ? $this->routeUrl('register') : null,
            'status' => $request->session()->get(Flash::STATUS),
        ]));
    }

    private function routeUrl(string $name): ?string
    {
        return Route::has($name) ? route($name) : null;
    }
}
