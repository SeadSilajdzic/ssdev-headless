<?php

namespace Ssdev\Headless\Api;

use Ssdev\Headless\Auth\RateLimits;
use Ssdev\Headless\Http\Middleware\EnsureApiIsEnabled;
use Ssdev\Headless\Http\Middleware\ForceJsonResponse;

final class ApiRoutes
{
    /**
     * ForceJsonResponse runs first, so errors from later middleware are rendered as JSON too.
     */
    public const MIDDLEWARE = [ForceJsonResponse::class, 'api', EnsureApiIsEnabled::class, 'throttle:'.RateLimits::API];
}
