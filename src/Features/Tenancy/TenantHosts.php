<?php

namespace Ssdev\Headless\Features\Tenancy;

use Illuminate\Contracts\Config\Repository;

final class TenantHosts
{
    public function __construct(private Repository $config) {}

    public function isCentral(string $host): bool
    {
        return strtolower($host) === $this->setting('central_domain');
    }

    /**
     * The tenant's subdomain, only for a host one label directly under the base domain.
     */
    public function subdomainOf(string $host): ?string
    {
        $suffix = '.'.$this->setting('base_domain');
        $host = strtolower($host);

        if (! str_ends_with($host, $suffix)) {
            return null;
        }

        $subdomain = substr($host, 0, -strlen($suffix));

        return $subdomain === '' || str_contains($subdomain, '.') ? null : $subdomain;
    }

    public function rootUrlFor(Tenant $tenant): string
    {
        $appUrl = (string) $this->config->get('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';
        $port = parse_url($appUrl, PHP_URL_PORT);

        return $scheme.'://'.$tenant->subdomain.'.'.$this->setting('base_domain').($port === null ? '' : ':'.$port);
    }

    private function setting(string $key): string
    {
        return strtolower((string) $this->config->get("headless.tenancy.{$key}"));
    }
}
