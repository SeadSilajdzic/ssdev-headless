<?php

namespace Ssdev\Headless\Features\Tenancy;

use Illuminate\Contracts\Config\Repository;
use Ssdev\Headless\Support\Isolation;

/**
 * Inside a tenant, files go under its own directory and keys carry its id; the central level keeps them unchanged.
 */
final class TenantIsolation implements Isolation
{
    public function __construct(private Repository $config) {}

    public function path(string $path): string
    {
        $tenant = TenantContext::currentKey();

        return $tenant === TenantContext::CENTRAL ? $path : $this->config->get('headless.tenancy.storage_directory').'/'.$tenant.'/'.$path;
    }

    public function key(string $key): string
    {
        $tenant = TenantContext::currentKey();

        return $tenant === TenantContext::CENTRAL ? $key : self::tenantKey($tenant, $key);
    }

    public static function tenantKey(int $tenant, string $key): string
    {
        return 'tenant:'.$tenant.'|'.$key;
    }
}
