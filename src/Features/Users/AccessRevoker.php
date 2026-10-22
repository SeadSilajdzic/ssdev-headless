<?php

namespace Ssdev\Headless\Features\Users;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Ssdev\Headless\Account\PersonalAccessTokenService;
use Ssdev\Headless\Account\SessionRepository;

final class AccessRevoker
{
    private const REMEMBER_TOKEN_LENGTH = 60;

    public function __construct(
        private SessionRepository $sessions,
        private PersonalAccessTokenService $tokens,
    ) {}

    /**
     * A new remember token also ends the "remember me" cookies, which outlive the sessions.
     */
    public function endSessions(User $user): void
    {
        $this->sessions->deleteForUser($user);

        $user->forceFill(['remember_token' => Str::random(self::REMEMBER_TOKEN_LENGTH)])->save();
    }

    /**
     * Only the database session driver keeps sessions that can be ended, so with any other the request is refused
     * instead of reporting success. Blocking does not depend on it: blocked users are signed out on their next request.
     */
    public function signOutEverywhere(User $user): void
    {
        if (! $this->sessions->supportsBrowserSessions()) {
            throw ValidationException::withMessages(['user' => __('headless::users.errors.sessions_unsupported')]);
        }

        $this->endSessions($user);
    }

    public function supportsSigningOut(): bool
    {
        return $this->sessions->supportsBrowserSessions();
    }

    public function endAllAccess(User $user): void
    {
        $this->endSessions($user);
        $this->tokens->revokeAll($user);
    }
}
