<?php

namespace Ssdev\Headless\Features\Users;

use Illuminate\Foundation\Auth\User;

enum UserStatus: string
{
    case Active = 'active';
    case Invited = 'invited';
    case Blocked = 'blocked';

    public static function of(User $user): self
    {
        if ($user->getAttribute('blocked_at') !== null) {
            return self::Blocked;
        }

        return $user->getAttribute('invited_at') !== null ? self::Invited : self::Active;
    }
}
