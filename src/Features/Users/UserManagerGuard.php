<?php

namespace Ssdev\Headless\Features\Users;

use Illuminate\Foundation\Auth\User;
use Illuminate\Validation\ValidationException;

final class UserManagerGuard
{
    public function ensureNotSelf(User $actor, User $user): void
    {
        if ($actor->is($user)) {
            throw ValidationException::withMessages(['user' => __('headless::users.errors.self')]);
        }
    }
}
