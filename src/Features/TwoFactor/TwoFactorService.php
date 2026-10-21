<?php

namespace Ssdev\Headless\Features\TwoFactor;

use Illuminate\Foundation\Auth\User;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\TwoFactorAuthenticatable;
use LogicException;

final class TwoFactorService
{
    public function __construct(
        private EnableTwoFactorAuthentication $enableAction,
        private ConfirmTwoFactorAuthentication $confirmAction,
        private GenerateNewRecoveryCodes $recoveryCodesAction,
        private DisableTwoFactorAuthentication $disableAction,
        private TwoFactorAuthenticationProvider $provider,
    ) {}

    /**
     * Without Fortify's trait the sign-in never asks for a code, so such a user counts as disabled.
     */
    public function status(User $user): TwoFactorStatus
    {
        if (! $this->supportsTwoFactor($user) || $user->getAttribute('two_factor_secret') === null) {
            return TwoFactorStatus::Disabled;
        }

        return $user->getAttribute('two_factor_confirmed_at') === null ? TwoFactorStatus::Pending : TwoFactorStatus::Enabled;
    }

    /**
     * Enabling again before the confirmation starts over with a new secret.
     */
    public function enable(User $user): TwoFactorSetup
    {
        if (! $this->supportsTwoFactor($user)) {
            throw new LogicException('Add the Laravel\Fortify\TwoFactorAuthenticatable trait to your User model.');
        }

        if ($this->status($user) === TwoFactorStatus::Enabled) {
            throw ValidationException::withMessages(['two_factor' => __('headless::two_factor.errors.already_enabled')]);
        }

        ($this->enableAction)($user, true);

        return $this->pendingSetup($user);
    }

    public function setup(User $user): ?TwoFactorSetup
    {
        return $this->status($user) === TwoFactorStatus::Pending ? $this->pendingSetup($user) : null;
    }

    /**
     * @return list<string> recovery codes, to be shown once
     */
    public function confirm(User $user, #[\SensitiveParameter] string $code): array
    {
        if ($this->status($user) !== TwoFactorStatus::Pending) {
            throw $this->invalidCode();
        }

        try {
            ($this->confirmAction)($user, $code);
        } catch (ValidationException) {
            throw $this->invalidCode();
        }

        return $this->recoveryCodes($user);
    }

    /**
     * @return list<string> recovery codes, to be shown once
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        if ($this->status($user) !== TwoFactorStatus::Enabled) {
            throw ValidationException::withMessages(['two_factor' => __('headless::two_factor.errors.not_enabled')]);
        }

        ($this->recoveryCodesAction)($user);

        return $this->recoveryCodes($user);
    }

    public function disable(User $user): void
    {
        ($this->disableAction)($user);
    }

    /**
     * Fortify's provider remembers accepted codes, so a code works only once.
     */
    public function verifyCode(User $user, #[\SensitiveParameter] string $code): bool
    {
        return $this->status($user) === TwoFactorStatus::Enabled && $this->provider->verify($this->secret($user), $code);
    }

    public function useRecoveryCode(User $user, #[\SensitiveParameter] string $code): bool
    {
        if ($this->status($user) !== TwoFactorStatus::Enabled || ! method_exists($user, 'replaceRecoveryCode')) {
            return false;
        }

        foreach ($this->recoveryCodes($user) as $recoveryCode) {
            if (hash_equals($recoveryCode, $code)) {
                $user->replaceRecoveryCode($recoveryCode);

                return true;
            }
        }

        return false;
    }

    private function pendingSetup(User $user): TwoFactorSetup
    {
        if (! method_exists($user, 'twoFactorQrCodeSvg') || ! method_exists($user, 'twoFactorQrCodeUrl')) {
            throw new LogicException('Add the Laravel\Fortify\TwoFactorAuthenticatable trait to your User model.');
        }

        return new TwoFactorSetup($this->secret($user), $user->twoFactorQrCodeUrl(), $user->twoFactorQrCodeSvg());
    }

    private function secret(User $user): string
    {
        return Fortify::currentEncrypter()->decrypt($user->getAttribute('two_factor_secret'));
    }

    /**
     * @return list<string>
     */
    private function recoveryCodes(User $user): array
    {
        return array_values(json_decode(Fortify::currentEncrypter()->decrypt($user->getAttribute('two_factor_recovery_codes')), true));
    }

    private function invalidCode(): ValidationException
    {
        return ValidationException::withMessages(['code' => __('headless::two_factor.errors.invalid_code')]);
    }

    private function supportsTwoFactor(User $user): bool
    {
        return in_array(TwoFactorAuthenticatable::class, class_uses_recursive($user), true);
    }
}
