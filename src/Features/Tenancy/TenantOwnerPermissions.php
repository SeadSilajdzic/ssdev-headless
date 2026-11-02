<?php

namespace Ssdev\Headless\Features\Tenancy;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Auth\User;
use Spatie\Permission\PermissionRegistrar;
use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Features\Roles\RoleAssignmentService;
use Ssdev\Headless\Features\Roles\RoleGuard;
use Ssdev\Headless\Features\Roles\RolesModule;
use Ssdev\Headless\Permissions\GlobalAssignments;
use Ssdev\Headless\Permissions\PermissionRegistry;

/**
 * The owner holds every permission of the tenant: through the tenant's Administrator role with roles on, directly
 * otherwise. Call it inside the tenant.
 */
final class TenantOwnerPermissions
{
    public function __construct(
        private PermissionRegistry $permissions,
        private FeatureRegistry $features,
        private RoleGuard $roles,
        private RoleAssignmentService $roleAssignments,
        private GlobalAssignments $assignments,
        private PermissionRegistrar $registrar,
        private Repository $config,
    ) {}

    public function grant(User $owner): void
    {
        if ($this->tenantPermissions() === []) {
            return;
        }

        if ($this->features->isEnabled(Feature::Roles->value)) {
            $role = $this->roles->roles()->firstOrCreate(['name' => RolesModule::ADMIN_ROLE], ['guard_name' => $this->roles->name()]);
            $role->syncPermissions($this->tenantPermissions());
            $this->roleAssignments->assignWithoutActor($role, $owner);

            return;
        }

        // Global, so with teams on the grant never lands in the current team of the admin creating the tenant.
        if (method_exists($owner, 'permissions')) {
            $owner->permissions()->attach($this->tenantPermissionKeys(), $this->assignments->pivot());
        }
    }

    /**
     * @return list<string>
     */
    private function tenantPermissions(): array
    {
        return array_values(array_diff($this->permissions->names(), TenantPermission::names()));
    }

    /**
     * @return list<int|string>
     */
    private function tenantPermissionKeys(): array
    {
        $permissionClass = $this->registrar->getPermissionClass();

        return $permissionClass::query()
            ->where('guard_name', $this->config->get('auth.defaults.guard'))
            ->whereIn('name', $this->tenantPermissions())
            ->pluck((new $permissionClass)->getKeyName())
            ->all();
    }
}
