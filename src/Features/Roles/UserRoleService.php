<?php

namespace Ssdev\Headless\Features\Roles;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Auth\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Sets the roles of one user at once: every change follows the assignment rules, and one refusal undoes them all.
 */
final class UserRoleService
{
    public function __construct(
        private RoleRepository $roles,
        private RoleAssignmentService $assignments,
        private ConnectionInterface $database,
    ) {}

    /**
     * @param  list<int>  $roleIds
     */
    public function sync(User $actor, User $user, array $roleIds): void
    {
        $roles = $this->roles->all()->keyBy(fn (Role $role): int|string => $role->getKey());

        if (array_diff($roleIds, $roles->keys()->all()) !== []) {
            throw ValidationException::withMessages(['roles' => __('headless::roles.errors.unknown_role')]);
        }

        $current = $this->roles->assignedTo($user)->map(fn (Role $role) => $role->getKey())->all();

        $this->database->transaction(function () use ($actor, $user, $roles, $roleIds, $current): void {
            foreach (array_diff($roleIds, $current) as $added) {
                $this->assignments->assign($actor, $roles[$added], $user);
            }

            foreach (array_diff($current, $roleIds) as $removed) {
                $this->assignments->revoke($actor, $roles[$removed], $user);
            }
        });
    }
}
