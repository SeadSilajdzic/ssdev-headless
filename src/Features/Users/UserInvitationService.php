<?php

namespace Ssdev\Headless\Features\Users;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Notifications\Dispatcher as Notifications;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Ssdev\Headless\Account\UserRepository;
use Ssdev\Headless\Features\Users\Events\UserInvited;
use Ssdev\Headless\Features\Users\Notifications\UserInvitationNotification;

/**
 * An invitation is a password broker token with a longer life, so it works once, expires and fits only its email.
 */
final class UserInvitationService
{
    public const BROKER = 'headless_invitations';

    public function __construct(
        private UserRepository $users,
        private PasswordBrokerFactory $brokers,
        private Hasher $hasher,
        private Notifications $notifications,
        private Dispatcher $events,
    ) {}

    public function invite(User $actor, string $name, string $email): User
    {
        $user = $this->users->create([
            'name' => $name,
            'email' => mb_strtolower(trim($email)),
            // Nobody knows this password; the invitee chooses their own.
            'password' => $this->hasher->make(Str::random(64)),
            'invited_at' => now(),
        ]);

        $this->send($user);
        $this->events->dispatch(new UserInvited($user, $actor));

        return $user;
    }

    public function resend(User $user): void
    {
        if (UserStatus::of($user) !== UserStatus::Invited) {
            throw ValidationException::withMessages(['user' => __('headless::users.errors.not_invited')]);
        }

        if ($this->send($user) === PasswordBroker::RESET_THROTTLED) {
            throw ValidationException::withMessages(['user' => __('headless::users.errors.invitation_throttled')]);
        }
    }

    /**
     * Sends to the current email; the new token replaces any earlier one.
     */
    public function send(User $user): string
    {
        return $this->broker()->sendResetLink(
            ['email' => $user->getAttribute('email')],
            fn (CanResetPassword $invitee, string $token) => $this->notifications->send($invitee, new UserInvitationNotification($token)),
        );
    }

    public function accept(string $token, string $email, #[\SensitiveParameter] string $password): void
    {
        $status = $this->broker()->reset(
            ['email' => mb_strtolower(trim($email)), 'token' => $token, 'password' => $password],
            function (User $user, string $password): void {
                $user->forceFill(['password' => $this->hasher->make($password), 'remember_token' => Str::random(60)])->save();

                $this->events->dispatch(new PasswordReset($user));
            },
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __('headless::users.errors.invitation_invalid')]);
        }
    }

    private function broker(): PasswordBroker
    {
        return $this->brokers->broker(self::BROKER);
    }
}
