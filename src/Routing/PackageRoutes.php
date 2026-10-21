<?php

namespace Ssdev\Headless\Routing;

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Api\ApiRoutes;

final class PackageRoutes
{
    public static function web(string $routesFile): void
    {
        if (app()->routesAreCached()) {
            return;
        }

        Route::middleware(config('headless.routes.middleware'))
            ->prefix(config('headless.routes.prefix'))
            ->group($routesFile);
    }

    public static function api(string $routesFile): void
    {
        if (app()->routesAreCached()) {
            return;
        }

        Route::middleware(ApiRoutes::MIDDLEWARE)
            ->prefix(config('headless.api.prefix'))
            ->group($routesFile);
    }
}
