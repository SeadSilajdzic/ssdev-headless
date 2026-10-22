<?php

namespace Ssdev\Headless\Features\Tenancy\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Ssdev\Headless\Features\Tenancy\TenantHosts;
use Symfony\Component\HttpFoundation\Response;

/**
 * Any host that is neither the central domain nor a tenant's subdomain is refused, so it never falls back to the central level.
 */
final class IdentifyTenant
{
    public function __construct(
        private TenantHosts $hosts,
        private Repository $config,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if ($this->hosts->isCentral($host) || $request->is(...$this->config->get('headless.tenancy.any_host_paths', []))) {
            Tenant::forgetCurrent();

            return $next($request);
        }

        $subdomain = $this->hosts->subdomainOf($host);
        $tenant = $subdomain === null ? null : Tenant::query()->where('subdomain', $subdomain)->first();

        if ($tenant === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $tenant->makeCurrent();

        return $next($request);
    }
}
