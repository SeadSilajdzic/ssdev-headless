<?php

namespace Ssdev\Headless\Features\Roles;

use Illuminate\Foundation\Auth\User;
use Ssdev\Headless\Account\AdminAccess;
use Ssdev\Headless\Permissions\PermissionRegistry;

final class AdministratorRoleAccess implements AdminAccess
{
    public function __construct(
        private RoleGuard $guard,
        private PermissionRegistry $permissions,
        private RoleAssignmentService $assignments,
    ) {}

    public function grant(User $admin): void
    {
        $role = $this->guard->roles()->firstOrCreate(['name' => RolesModule::ADMIN_ROLE], ['guard_name' => $this->guard->name()]);
        $role->syncPermissions($this->permissions->names());

        $this->assignments->assignWithoutActor($role, $admin);
    }
}
