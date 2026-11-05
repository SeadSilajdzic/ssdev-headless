<?php

namespace Ssdev\Headless\Account;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Timebox;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

final class ApiTokenAuthenticator
{
    private const GUARD = 'sanctum';

    // Same minimum duration Laravel uses for session logins, so unknown emails answer as slowly as wrong passwords.
    private const MINIMUM_DURATION_MICROSECONDS = 200_000;

    public function __construct(
        private UserRepository $users,
        private Hasher $hasher,
        private Dispatcher $events,
        private Timebox $timebox,
        private PersonalAccessTokenService $tokens,
    ) {}

    public function authenticate(string $email, #[\SensitiveParameter] string $password): User
    {
        $email = mb_strtolower(trim($email));

        $user = $this->timebox->call(function (Timebox $timebox) use ($email, $password): ?User {
            $user = $this->users->findByEmail($email);

            if ($user !== null && $this->hasher->check($password, $user->getAuthPassword())) {
                $timebox->returnEarly();

                return $user;
            }

            return null;
        }, self::MINIMUM_DURATION_MICROSECONDS);

        if ($user === null) {
            $this->events->dispatch(new Failed(self::GUARD, $this->users->findByEmail($email), ['email' => $email]));

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        return $user;
    }

    /**
     * Trusts its caller, as Auth::login() does, so call it only after every sign-in check has passed.
     * Device tokens get "*": the token ability gate still limits them to the owner's permissions.
     */
    public function issueToken(User $user, string $deviceName): NewAccessToken
    {
        $this->events->dispatch(new Login(self::GUARD, $user, false));

        return $this->tokens->create($user, $deviceName, ['*']);
    }

    public function revokeCurrentToken(User $user): void
    {
        $this->tokens->currentToken($user)?->delete();
    }
}
