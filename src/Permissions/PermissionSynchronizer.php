<?php

namespace Ssdev\Headless\Permissions;

use Illuminate\Contracts\Config\Repository;
use Spatie\Permission\PermissionRegistrar;

final class PermissionSynchronizer
{
    public function __construct(
        private PermissionRegistry $registry,
        private PermissionRegistrar $registrar,
        private Repository $config,
    ) {}

    /**
     * Creates the registered permissions that are missing and keeps the ones the application added itself.
     */
    public function sync(): int
    {
        $guard = $this->config->get('auth.defaults.guard');
        $permissionClass = $this->registrar->getPermissionClass();

        $existing = $permissionClass::query()->where('guard_name', $guard)->pluck('name')->all();
        $missing = array_values(array_diff($this->registry->names(), $existing));

        foreach ($missing as $name) {
            $permissionClass::create(['name' => $name, 'guard_name' => $guard]);
        }

        $this->registrar->forgetCachedPermissions();

        return count($missing);
    }
}
