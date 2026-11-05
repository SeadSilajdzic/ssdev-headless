<?php

namespace Ssdev\Headless\Features\Roles;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Auth\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Account\UserRepository;

/**
 * Sets the roles of one user at once: every change follows the assignment rules, and one refusal undoes them all.
 */
final class UserRoleService
{
    public function __construct(
        private RoleRepository $roles,
        private RoleAssignmentService $assignments,
        private ConnectionInterface $database,
        private UserRepository $users,
    ) {}

    /**
     * @param  list<int>  $roleIds
     * @param  list<int>|null  $knownRoleIds  the roles the actor last saw; null replaces whatever is there now
     */
    public function sync(User $actor, User $user, array $roleIds, ?array $knownRoleIds = null): void
    {
        $roles = $this->roles->all()->keyBy(fn (Role $role): int|string => $role->getKey());

        if (array_diff($roleIds, $roles->keys()->all()) !== []) {
            throw ValidationException::withMessages(['roles' => __('headless::roles.errors.unknown_role')]);
        }

        $this->database->transaction(function () use ($actor, $user, $roles, $roleIds, $knownRoleIds): void {
            // Locked, so two managers saving at once cannot both pass the check below.
            $this->users->findForUpdate($user->getKey());
            $current = $this->roles->assignedTo($user)->map(fn (Role $role) => $role->getKey())->all();

            if ($knownRoleIds !== null && (array_diff($knownRoleIds, $current) !== [] || array_diff($current, $knownRoleIds) !== [])) {
                throw ValidationException::withMessages(['role' => __('headless::roles.errors.roles_changed')]);
            }

            foreach (array_diff($roleIds, $current) as $added) {
                $this->assignments->assign($actor, $roles[$added], $user);
            }

            foreach (array_diff($current, $roleIds) as $removed) {
                $this->assignments->revoke($actor, $roles[$removed], $user);
            }
        });
    }
}
