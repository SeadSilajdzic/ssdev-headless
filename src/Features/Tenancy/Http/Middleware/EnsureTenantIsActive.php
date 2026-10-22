<?php

namespace Ssdev\Headless\Features\Tenancy\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Ssdev\Headless\Http\Inertia\StandalonePage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every route of a suspended tenant answers with the suspension, sign-in included.
 */
final class EnsureTenantIsActive
{
    public const JSON = 'json';

    public function __construct(private StandalonePage $pages) {}

    public function handle(Request $request, Closure $next, ?string $format = null): Response
    {
        if (! Tenant::current()?->isSuspended()) {
            return $next($request);
        }

        if ($format === self::JSON) {
            return new JsonResponse(['message' => __('headless::tenancy.suspended.description')], Response::HTTP_FORBIDDEN);
        }

        return $this->pages->render($request, 'Tenancy/Suspended', [], Response::HTTP_FORBIDDEN);
    }
}
