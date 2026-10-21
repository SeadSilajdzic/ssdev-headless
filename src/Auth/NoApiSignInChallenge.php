<?php

namespace Ssdev\Headless\Auth;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Foundation\Auth\User;

final class NoApiSignInChallenge implements ApiSignInChallenge
{
    public function challenge(User $user, string $deviceName): ?Responsable
    {
        return null;
    }
}
