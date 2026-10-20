<?php

namespace Ssdev\Headless\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureRegistrationIsEnabled
{
    public function __construct(private Repository $config) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless((bool) $this->config->get('headless.registration.enabled'), Response::HTTP_NOT_FOUND);

        return $next($request);
    }
}
