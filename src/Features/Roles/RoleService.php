<?php

namespace Ssdev\Headless\Features\Roles;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Permissions\PermissionRegistry;

/**
 * Nobody can hand out a permission they do not hold, and some role that manages roles always keeps a member.
 */
final class RoleService
{
    public function __construct(
        private RoleGuard $guard,
        private PermissionRegistry $permissions,
        private ConnectionInterface $database,
    ) {}

    public function create(User $actor, RoleData $data): Role
    {
        $this->ensureGrantable($actor, $data->permissions);

        return $this->database->transaction(function () use ($data): Role {
            $role = $this->guard->roles()->create(['name' => $data->name, 'guard_name' => $this->guard->name()]);
            $role->syncPermissions($data->permissions);

            return $role;
        });
    }

    public function update(User $actor, Role $role, RoleData $data): Role
    {
        $this->ensureManageable($actor, $role);
        $this->ensureGrantable($actor, $data->permissions);

        return $this->database->transaction(function () use ($role, $data): Role {
            $this->ensureManagersRemain($role, permissionsAfter: $data->permissions);

            $role->update(['name' => $data->name]);
            $role->syncPermissions($data->permissions);

            return $role->refresh();
        });
    }

    public function delete(User $actor, Role $role): void
    {
        $this->ensureManageable($actor, $role);

        $this->database->transaction(function () use ($role): void {
            $this->ensureManagersRemain($role, permissionsAfter: []);

            $role->delete();
        });
    }

    /**
     * A role can only be changed, deleted or handed out by someone who holds every permission in it.
     */
    public function ensureManageable(User $actor, Role $role): void
    {
        foreach ($role->permissions->pluck('name') as $permission) {
            if (! $actor->can($permission)) {
                throw ValidationException::withMessages(['role' => __('headless::roles.errors.role_not_manageable')]);
            }
        }
    }

    /**
     * Call inside a transaction: the managing roles stay locked until it ends, so two requests cannot both remove the last one.
     *
     * @param  list<string>|null  $permissionsAfter
     */
    public function ensureManagersRemain(Role $role, ?User $leaving = null, ?array $permissionsAfter = null): void
    {
        $managing = $this->managingRoles();

        if ($managing->where('users_count', '>', 0)->isEmpty()) {
            return;
        }

        $remaining = $managing->filter(function (Role $managingRole) use ($role, $leaving, $permissionsAfter): bool {
            if (! $managingRole->is($role)) {
                return $managingRole->users_count > 0;
            }

            if ($permissionsAfter !== null && ! in_array(RolePermission::Manage->value, $permissionsAfter, true)) {
                return false;
            }

            $leaves = $leaving !== null && $role->users()->whereKey($leaving->getKey())->exists();

            return $managingRole->users_count - ($leaves ? 1 : 0) > 0;
        });

        if ($remaining->isEmpty()) {
            throw ValidationException::withMessages(['role' => __('headless::roles.errors.last_manager')]);
        }
    }

    /**
     * @param  list<string>  $permissions
     */
    private function ensureGrantable(User $actor, array $permissions): void
    {
        $registered = $this->permissions->names();

        foreach ($permissions as $permission) {
            if (! in_array($permission, $registered, true)) {
                throw ValidationException::withMessages(['permissions' => __('headless::roles.errors.unknown_permission')]);
            }

            if (! $actor->can($permission)) {
                throw ValidationException::withMessages(['permissions' => __('headless::roles.errors.permission_not_held')]);
            }
        }
    }

    /**
     * @return Collection<int, Role>
     */
    private function managingRoles(): Collection
    {
        return $this->guard->roles()
            ->whereHas('permissions', fn ($query) => $query->where('name', RolePermission::Manage->value))
            ->withCount('users')
            ->lockForUpdate()
            ->get();
    }
}
