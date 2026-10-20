<?php

namespace Ssdev\Headless\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireVerifiedEmail
{
    public function __construct(
        private Repository $config,
        private EnsureEmailIsVerified $verified,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->config->get('headless.email_verification')) {
            return $next($request);
        }

        return $this->verified->handle($request, $next);
    }
}
