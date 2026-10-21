<?php

namespace Ssdev\Headless\Features\Users;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
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

    public function endAllAccess(User $user): void
    {
        $this->endSessions($user);
        $this->tokens->revokeAll($user);
    }
}
