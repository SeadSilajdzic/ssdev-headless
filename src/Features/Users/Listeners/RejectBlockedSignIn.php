<?php

namespace Ssdev\Headless\Features\Users\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Foundation\Auth\User;
use Illuminate\Validation\ValidationException;
use Ssdev\Headless\Features\Users\UserStatus;

/**
 * Login fires after the password matched — on the web, after a two-factor challenge and before an API token is made —
 * so a blocked user learns why only once they proved who they are.
 */
final class RejectBlockedSignIn
{
    public function __construct(private AuthFactory $auth) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User || UserStatus::of($event->user) !== UserStatus::Blocked) {
            return;
        }

        $guard = $this->auth->guard($event->guard);

        if ($guard instanceof StatefulGuard) {
            $guard->logout();
        }

        throw ValidationException::withMessages(['email' => __('headless::users.errors.blocked')]);
    }
}
