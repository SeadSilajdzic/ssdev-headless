<?php

namespace Ssdev\Headless\Features\Tenancy\Tasks;

use Illuminate\Contracts\Config\Repository;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;
use Spatie\Permission\PermissionRegistrar;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Ssdev\Headless\Features\Tenancy\TenantIsolation;

/**
 * Roles differ per tenant, so the cached permissions and their roles are kept under a key of the tenant's own;
 * a key rather than the store's prefix, as the array store has none.
 */
final class SwitchPermissionCache implements SwitchTenantTask
{
    private const KEY = 'permission.cache.key';

    private string $centralKey;

    public function __construct(
        private Repository $config,
        private PermissionRegistrar $registrar,
    ) {
        $this->centralKey = (string) $config->get(self::KEY);
    }

    public function makeCurrent(IsTenant $tenant): void
    {
        if ($tenant instanceof Tenant) {
            $this->useKey(TenantIsolation::tenantKey($tenant->id, $this->centralKey));
        }
    }

    public function forgetCurrent(): void
    {
        $this->useKey($this->centralKey);
    }

    private function useKey(string $key): void
    {
        $this->config->set(self::KEY, $key);
        // Also drops the roles the registrar loaded for the previous tenant.
        $this->registrar->initializeCache();
    }
}
