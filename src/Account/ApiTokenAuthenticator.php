<?php

namespace Ssdev\Headless\Account;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Timebox;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use LogicException;

final class ApiTokenAuthenticator
{
    private const GUARD = 'sanctum';

    // Same minimum duration Laravel uses for session logins, so unknown emails answer as slowly as wrong passwords.
    private const MINIMUM_DURATION_MICROSECONDS = 200_000;

    public function __construct(
        private UserRepository $users,
        private Hasher $hasher,
        private Dispatcher $events,
        private Repository $config,
        private Timebox $timebox,
    ) {}

    /**
     * Device tokens get "*": the token ability gate still limits them to the owner's permissions.
     */
    public function issue(string $email, #[\SensitiveParameter] string $password, string $deviceName): NewAccessToken
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

        if (! method_exists($user, 'createToken')) {
            throw new LogicException('Add the Laravel\Sanctum\HasApiTokens trait to your User model.');
        }

        $this->events->dispatch(new Login(self::GUARD, $user, false));

        return $user->createToken($deviceName, ['*'], $this->expiresAt());
    }

    public function revokeCurrentToken(User $user): void
    {
        $token = method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;

        // Cookie-authenticated SPA requests carry a transient token that has nothing to delete.
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    private function expiresAt(): ?CarbonImmutable
    {
        $minutes = $this->config->get('headless.api.token_expiration');

        return $minutes === null ? null : CarbonImmutable::now()->addMinutes((int) $minutes);
    }
}
