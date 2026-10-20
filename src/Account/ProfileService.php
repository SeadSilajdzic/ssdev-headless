<?php

namespace Ssdev\Headless\Account;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User;

final class ProfileService
{
    public function __construct(private EmailChangeService $emailChanges) {}

    public function update(User $user, string $name, string $email): ProfileUpdateOutcome
    {
        $user->forceFill(['name' => $name]);

        if ($email === $user->getAttribute('email')) {
            $user->save();

            return ProfileUpdateOutcome::Saved;
        }

        // An address that was never verified has nothing to protect, so it changes right away.
        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            $user->forceFill(['email' => $email, 'email_verified_at' => null])->save();
            $user->sendEmailVerificationNotification();

            return ProfileUpdateOutcome::Saved;
        }

        $user->save();
        $this->emailChanges->request($user, $email);

        return ProfileUpdateOutcome::EmailConfirmationSent;
    }
}
