<?php

namespace Ssdev\Headless\Features\TwoFactor;

use Illuminate\Foundation\Auth\User;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Laravel\Sanctum\NewAccessToken;
use Ssdev\Headless\Account\ApiTokenAuthenticator;
use Ssdev\Headless\Account\UserRepository;

final class ApiTwoFactorChallengeService
{
    public function __construct(
        private TwoFactorChallengeStore $challenges,
        private TwoFactorService $twoFactor,
        private UserRepository $users,
        private ApiTokenAuthenticator $authenticator,
    ) {}

    /**
     * A wrong answer keeps the challenge open until it expires; the rate limit caps the guesses.
     */
    public function complete(#[\SensitiveParameter] string $challengeToken, #[\SensitiveParameter] ?string $code, #[\SensitiveParameter] ?string $recoveryCode): NewAccessToken
    {
        $challenge = $this->challenges->find($challengeToken);
        $user = $challenge === null ? null : $this->users->find($challenge['user_id']);

        if ($challenge === null || $user === null) {
            throw ValidationException::withMessages(['challenge_token' => __('headless::two_factor.errors.challenge_expired')]);
        }

        if (! $this->answers($user, $code, $recoveryCode)) {
            TwoFactorAuthenticationFailed::dispatch($user);

            throw ValidationException::withMessages($recoveryCode !== null
                ? ['recovery_code' => __('headless::two_factor.errors.invalid_recovery_code')]
                : ['code' => __('headless::two_factor.errors.invalid_code')]);
        }

        if (! $this->challenges->consume($challengeToken)) {
            throw ValidationException::withMessages(['challenge_token' => __('headless::two_factor.errors.challenge_expired')]);
        }

        ValidTwoFactorAuthenticationCodeProvided::dispatch($user);

        return $this->authenticator->issueToken($user, $challenge['device_name']);
    }

    private function answers(User $user, ?string $code, ?string $recoveryCode): bool
    {
        if ($recoveryCode !== null) {
            return $this->twoFactor->useRecoveryCode($user, $recoveryCode);
        }

        return $code !== null && $this->twoFactor->verifyCode($user, $code);
    }
}
