<?php

namespace Ssdev\Headless\Features\Roles;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Auth\User;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Features\Roles\Events\RoleAssigned;
use Ssdev\Headless\Features\Roles\Events\RoleRevoked;

final class RoleAssignmentService
{
    public function __construct(
        private RoleService $roles,
        private ConnectionInterface $database,
        private Dispatcher $events,
    ) {}

    public function assign(User $actor, Role $role, User $user): void
    {
        $this->roles->ensureManageable($actor, $role);

        if ($this->isMember($role, $user)) {
            return;
        }

        $role->users()->attach($user->getKey());

        $this->events->dispatch(new RoleAssigned($role, $user, $actor));
    }

    public function revoke(User $actor, Role $role, User $user): void
    {
        $this->roles->ensureManageable($actor, $role);

        $revoked = $this->database->transaction(function () use ($role, $user): int {
            $this->roles->ensureManagersRemain($role, leaving: $user);

            return $role->users()->detach($user->getKey());
        });

        if ($revoked > 0) {
            $this->events->dispatch(new RoleRevoked($role, $user, $actor));
        }
    }

    public function isMember(Role $role, User $user): bool
    {
        return $role->users()->whereKey($user->getKey())->exists();
    }
}
