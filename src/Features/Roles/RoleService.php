<?php

namespace Ssdev\Headless\Features\Roles;

use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Auth\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Features\Users\UserManagerGuard;
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
        private FeatureRegistry $features,
        private UserManagerGuard $userManagers,
    ) {}

    public function create(User $actor, RoleData $data): Role
    {
        $this->ensureGrantable($actor, $data->permissions);

        return $this->rejectingTakenName(fn (): Role => $this->database->transaction(function () use ($data): Role {
            $role = $this->guard->roles()->create(['name' => $data->name, 'guard_name' => $this->guard->name()]);
            $role->syncPermissions($data->permissions);

            return $role;
        }));
    }

    public function update(User $actor, Role $role, RoleData $data): Role
    {
        $this->ensureManageable($actor, $role);
        $this->ensureGrantable($actor, $data->permissions);

        return $this->rejectingTakenName(fn (): Role => $this->database->transaction(function () use ($role, $data): Role {
            $this->ensureManagersRemain($role, permissionsAfter: $data->permissions);

            return $this->keepingUserManagers(function () use ($role, $data): Role {
                $role->update(['name' => $data->name]);
                $role->syncPermissions($data->permissions);

                return $role->refresh();
            });
        }));
    }

    public function delete(User $actor, Role $role): void
    {
        $this->ensureManageable($actor, $role);

        $this->database->transaction(function () use ($role): void {
            $this->ensureManagersRemain($role, permissionsAfter: []);

            $this->keepingUserManagers(fn () => $role->delete());
        });
    }

    /**
     * With user management on, a role change must also leave someone active who can block and unblock users.
     * Call inside a transaction.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $change
     * @return TResult
     */
    public function keepingUserManagers(Closure $change): mixed
    {
        if (! $this->features->isEnabled(Feature::Users->value)) {
            return $change();
        }

        return $this->userManagers->ensureManagersRemainAfter($change, 'role');
    }

    /**
     * A role can only be changed, deleted or handed out by someone who holds every permission in it.
     */
    public function canManage(User $actor, Role $role): bool
    {
        return $role->permissions->every(fn ($permission): bool => $actor->can($permission->name));
    }

    public function ensureManageable(User $actor, Role $role): void
    {
        if (! $this->canManage($actor, $role)) {
            throw ValidationException::withMessages(['role' => __('headless::roles.errors.role_not_manageable')]);
        }
    }

    /**
     * Call inside a transaction: the managing roles stay locked until it ends, so two requests cannot both remove the last one.
     *
     * @param  list<string>|null  $permissionsAfter
     */
    public function ensureManagersRemain(Role $role, ?User $leaving = null, ?array $permissionsAfter = null): void
    {
        $stillManaging = $permissionsAfter === null || in_array(RolePermission::Manage->value, $permissionsAfter, true);

        $this->ensureSomeManagerRemains(function (int|string $roleKey, array $memberKeys) use ($role, $leaving, $stillManaging): array {
            if ($roleKey !== $role->getKey()) {
                return $memberKeys;
            }

            if (! $stillManaging) {
                return [];
            }

            return $leaving === null ? $memberKeys : array_diff($memberKeys, [$leaving->getKey()]);
        });
    }

    /**
     * For a user who leaves every role at once, e.g. when the account is deleted. Call inside a transaction.
     */
    public function ensureManagersRemainWithout(User $leaving): void
    {
        $this->ensureSomeManagerRemains(fn (int|string $roleKey, array $memberKeys): array => array_diff($memberKeys, [$leaving->getKey()]));
    }

    /**
     * Only the step from some managing role with a member to none is refused, so a fresh install can still be set up.
     *
     * @param  Closure(int|string, list<int|string>): array<int|string>  $membersAfter
     */
    private function ensureSomeManagerRemains(Closure $membersAfter): void
    {
        $members = $this->managingRoleMembers();

        if (! collect($members)->contains(fn (array $memberKeys): bool => $memberKeys !== [])) {
            return;
        }

        foreach ($members as $roleKey => $memberKeys) {
            if ($membersAfter($roleKey, $memberKeys) !== []) {
                return;
            }
        }

        throw ValidationException::withMessages(['role' => __('headless::roles.errors.last_manager')]);
    }

    /**
     * The request checks the name first; this covers a parallel request that took it in between.
     *
     * @param  Closure(): Role  $save
     */
    private function rejectingTakenName(Closure $save): Role
    {
        try {
            return $save();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => __('validation.unique', ['attribute' => 'name'])]);
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
     * Member keys per managing role. Locked reads, so they see a parallel removal once it commits (MySQL would otherwise
     * count from its snapshot); deleted users are left out by the user model's own scope.
     *
     * @return array<int|string, list<int|string>>
     */
    private function managingRoleMembers(): array
    {
        $roles = $this->guard->roles()
            ->whereHas('permissions', fn ($query) => $query->where('name', RolePermission::Manage->value))
            ->lockForUpdate()
            ->get();

        return $roles->mapWithKeys(function (Role $role): array {
            $members = $role->users();

            // A blocked member cannot manage anything, so they do not keep the role in use.
            if ($this->features->isEnabled(Feature::Users->value)) {
                $members->whereNull($members->getRelated()->qualifyColumn('blocked_at'));
            }

            return [$role->getKey() => $members->lockForUpdate()->pluck($members->getRelated()->getQualifiedKeyName())->all()];
        })->all();
    }
}
