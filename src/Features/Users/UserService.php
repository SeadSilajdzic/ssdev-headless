<?php

namespace Ssdev\Headless\Features\Users;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Auth\User;
use Ssdev\Headless\Events\AccountDeleting;
use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Features\Roles\RoleService;
use Ssdev\Headless\Features\Users\Events\UserDeleted;

final class UserService
{
    public function __construct(
        private UserManagerGuard $guard,
        private AccessRevoker $access,
        private UserInvitationService $invitations,
        private RoleService $roles,
        private FeatureRegistry $features,
        private ConnectionInterface $database,
        private Dispatcher $events,
    ) {}

    public function update(User $user, string $name, string $email): User
    {
        $email = mb_strtolower(trim($email));
        $emailChanged = $email !== $user->getAttribute('email');

        $user->forceFill(['name' => $name, 'email' => $email]);

        if ($emailChanged) {
            $user->forceFill(['email_verified_at' => null]);
        }

        $user->save();

        if ($emailChanged) {
            $this->confirmNewEmail($user);
        }

        return $user;
    }

    public function delete(User $actor, User $user): void
    {
        $this->guard->ensureNotSelf($actor, $user);

        $this->database->transaction(function () use ($user): void {
            $this->guard->ensureManagersRemain($user);

            if ($this->features->isEnabled(Feature::Roles->value)) {
                $this->roles->ensureManagersRemainWithout($user);
            }

            $this->events->dispatch(new AccountDeleting($user, permanent: false));
            $this->access->endAllAccess($user);
            $user->delete();
        });

        $this->events->dispatch(new UserDeleted($user->getKey(), (string) $user->getAttribute('email'), $actor));
    }

    /**
     * Accepting an invitation proves the email as well, so an invitee gets a new invitation instead of a verification mail.
     */
    private function confirmNewEmail(User $user): void
    {
        if (UserStatus::of($user) === UserStatus::Invited) {
            $this->invitations->send($user);

            return;
        }

        if ($user instanceof MustVerifyEmail) {
            $user->sendEmailVerificationNotification();
        }
    }
}
