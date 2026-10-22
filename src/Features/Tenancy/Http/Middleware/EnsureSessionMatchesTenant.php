<?php

namespace Ssdev\Headless\Features\Tenancy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ssdev\Headless\Features\Tenancy\TenantContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * A session cookie shared across subdomains must not carry sign-ins, password confirmations or 2FA state between tenants.
 */
final class EnsureSessionMatchesTenant
{
    private const SESSION_KEY = 'headless.tenant';

    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();
        $tenant = TenantContext::currentKey();

        if ($session->has(self::SESSION_KEY) && $session->get(self::SESSION_KEY) !== $tenant) {
            $session->invalidate();
            $session->regenerateToken();
        }

        $session->put(self::SESSION_KEY, $tenant);

        return $next($request);
    }
}
