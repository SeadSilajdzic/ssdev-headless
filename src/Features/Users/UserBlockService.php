<?php

namespace Ssdev\Headless\Features\Users;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Auth\User;
use Ssdev\Headless\Features\Users\Events\UserBlocked;
use Ssdev\Headless\Features\Users\Events\UserUnblocked;

/**
 * Blocking needs users.block, so an actor who may not block themselves always leaves an active user manager behind.
 */
final class UserBlockService
{
    public function __construct(
        private UserManagerGuard $guard,
        private AccessRevoker $access,
        private Dispatcher $events,
    ) {}

    public function block(User $actor, User $user): void
    {
        $this->guard->ensureNotSelf($actor, $user);

        if (UserStatus::of($user) === UserStatus::Blocked) {
            return;
        }

        $user->forceFill(['blocked_at' => now()])->save();
        $this->access->endAllAccess($user);

        $this->events->dispatch(new UserBlocked($user, $actor));
    }

    public function unblock(User $actor, User $user): void
    {
        if (UserStatus::of($user) !== UserStatus::Blocked) {
            return;
        }

        $user->forceFill(['blocked_at' => null])->save();

        $this->events->dispatch(new UserUnblocked($user, $actor));
    }
}
