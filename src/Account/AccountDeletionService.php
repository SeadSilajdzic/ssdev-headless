<?php

namespace Ssdev\Headless\Account;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Auth\User;
use Ssdev\Headless\Events\AccountDeleted;

final class AccountDeletionService
{
    public function __construct(
        private SessionRepository $sessions,
        private AvatarService $avatars,
        private ConnectionInterface $database,
        private Dispatcher $events,
    ) {}

    public function delete(User $user): void
    {
        $userKey = $user->getKey();
        $email = (string) $user->getAttribute('email');
        $avatar = $user->getAttribute('avatar_path');

        $this->database->transaction(function () use ($user) {
            $this->sessions->deleteForUser($user);

            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            $user->delete();
        });

        // Removed only after the commit, so a failed deletion never leaves an account without its photo.
        if (is_string($avatar)) {
            $this->avatars->discard($avatar);
        }

        $this->events->dispatch(new AccountDeleted($userKey, $email));
    }
}
