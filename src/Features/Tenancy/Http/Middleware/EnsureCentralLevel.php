<?php

namespace Ssdev\Headless\Features\Tenancy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCentralLevel
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Tenant::current() !== null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return $next($request);
    }
}
