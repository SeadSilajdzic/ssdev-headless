<?php

namespace Ssdev\Headless\Features\Users;

use Closure;
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
     * For a change that may take users.block away, such as a role edit. Call inside a transaction: the change runs
     * first, and refusing it rolls the transaction back.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $change
     * @return TResult
     */
    public function ensureManagersRemainAfter(Closure $change, string $errorField): mixed
    {
        $before = $this->users->lockActiveHoldersOf(UserPermission::Block->value);
        $result = $change();

        if ($before !== [] && $this->users->lockActiveHoldersOf(UserPermission::Block->value) === []) {
            throw ValidationException::withMessages([$errorField => __('headless::users.errors.last_manager')]);
        }

        return $result;
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
