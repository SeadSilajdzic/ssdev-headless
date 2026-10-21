<?php

namespace Ssdev\Headless\Features\Users;

use Illuminate\Foundation\Auth\User;
use Illuminate\Validation\ValidationException;

/**
 * A user manager is an active user with users.block: without one, nobody can let a blocked user back in.
 */
final class UserManagerGuard
{
    public function __construct(private ManagedUserRepository $users) {}

    public function ensureNotSelf(User $actor, User $user): void
    {
        if ($actor->is($user)) {
            throw ValidationException::withMessages(['user' => __('headless::users.errors.self')]);
        }
    }

    /**
     * Call inside a transaction: the managers stay locked until it ends, so two removals cannot both take the last one.
     */
    public function ensureManagersRemain(User $leaving): void
    {
        $managers = array_unique($this->users->lockActiveHoldersOf(UserPermission::Block->value));

        if ($managers === [$leaving->getKey()]) {
            throw ValidationException::withMessages(['user' => __('headless::users.errors.last_manager')]);
        }
    }
}
