<?php

namespace Ssdev\Headless\Auth\Actions;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Ssdev\Headless\Account\PersonalAccessTokenService;
use Ssdev\Headless\Account\SessionRepository;

final class ResetUserPassword implements ResetsUserPasswords
{
    public function __construct(
        private Hasher $hasher,
        private SessionRepository $sessions,
        private PersonalAccessTokenService $tokens,
    ) {}

    /**
     * Fortify's contract hands over the raw input, so validation happens here instead of a FormRequest.
     *
     * @param  array<string, mixed>  $input
     */
    public function reset(User $user, array $input): void
    {
        $validated = Validator::make($input, [
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ])->validate();

        $user->forceFill(['password' => $this->hasher->make($validated['password'])])->save();

        // Whoever held the account before the reset loses every session and API token.
        $this->sessions->deleteForUser($user);
        $this->tokens->revokeAll($user);
    }
}
