<?php

namespace Ssdev\Headless\Features\TwoFactor;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Foundation\Auth\User;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Ssdev\Headless\Auth\ApiSignInChallenge;
use Ssdev\Headless\Features\TwoFactor\Http\Resources\TwoFactorChallengeResource;

final class TwoFactorApiSignInChallenge implements ApiSignInChallenge
{
    public function __construct(
        private TwoFactorService $twoFactor,
        private TwoFactorChallengeStore $challenges,
    ) {}

    public function challenge(User $user, string $deviceName): ?Responsable
    {
        if ($this->twoFactor->status($user) !== TwoFactorStatus::Enabled) {
            return null;
        }

        $challenge = $this->challenges->create($user, $deviceName);

        TwoFactorAuthenticationChallenged::dispatch($user);

        return TwoFactorChallengeResource::make($challenge);
    }
}
