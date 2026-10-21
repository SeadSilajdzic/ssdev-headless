<?php

namespace Ssdev\Headless\Account;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use Ssdev\Headless\Events\PasswordChanged;

final class PasswordService
{
    public function __construct(
        private Hasher $hasher,
        private SessionRepository $sessions,
        private PersonalAccessTokenService $tokens,
        private Dispatcher $events,
    ) {}

    /**
     * Every other session, "remember me" cookie and API token stops working; the current session or token, if any, stays signed in.
     */
    public function change(User $user, #[\SensitiveParameter] string $password, ?string $currentSessionId, int|string|null $currentTokenId = null): void
    {
        $user->forceFill([
            'password' => $this->hasher->make($password),
            'remember_token' => Str::random(60),
        ])->save();

        $this->sessions->deleteForUser($user, $currentSessionId);
        $this->tokens->revokeAll($user, $currentTokenId);

        $this->events->dispatch(new PasswordChanged($user));
    }
}
