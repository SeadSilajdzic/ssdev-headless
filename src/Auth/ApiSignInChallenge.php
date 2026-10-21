<?php

namespace Ssdev\Headless\Auth;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Foundation\Auth\User;

/**
 * Lets a feature ask for a second step after the password is verified and before a token is issued.
 */
interface ApiSignInChallenge
{
    /**
     * @return Responsable|null the challenge to send instead of a token, or null to issue the token
     */
    public function challenge(User $user, string $deviceName): ?Responsable;
}
