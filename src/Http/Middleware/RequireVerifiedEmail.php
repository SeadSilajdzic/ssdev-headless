<?php

namespace Ssdev\Headless\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireVerifiedEmail
{
    // For pages guests may open too: only a signed-in account has to be verified.
    public const SIGNED_IN = 'signed-in';

    public function __construct(
        private Repository $config,
        private EnsureEmailIsVerified $verified,
    ) {}

    public function handle(Request $request, Closure $next, ?string $who = null): Response
    {
        if (! $this->config->get('headless.email_verification') || ($who === self::SIGNED_IN && $request->user() === null)) {
            return $next($request);
        }

        return $this->verified->handle($request, $next);
    }
}
