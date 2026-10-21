<?php

namespace Ssdev\Headless\Features\Roles;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;
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

        $this->attach($role, $user, $actor);
    }

    /**
     * For trusted callers such as the console, where no signed-in user hands the role out.
     */
    public function assignWithoutActor(Role $role, User $user): void
    {
        $this->attach($role, $user, null);
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

    private function attach(Role $role, User $user, ?User $actor): void
    {
        if ($this->isMember($role, $user)) {
            return;
        }

        try {
            // The savepoint keeps an outer PostgreSQL transaction usable when a parallel request added the member first.
            $this->database->transaction(fn () => $role->users()->attach($user->getKey()));
        } catch (UniqueConstraintViolationException) {
            return;
        }

        $this->events->dispatch(new RoleAssigned($role, $user, $actor));
    }
}
