<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Ssdev\Headless\Features\TwoFactor\TwoFactorService;
use Ssdev\Headless\Features\TwoFactor\TwoFactorStatus;
use Workbench\App\Models\User;

it('keeps two-factor authentication pending until a code confirms it', function () {
    $user = User::factory()->create();
    $setup = app(TwoFactorService::class)->enable($user);

    expect(app(TwoFactorService::class)->status($user->fresh()))->toBe(TwoFactorStatus::Pending)
        ->and($setup->otpauthUrl)->toStartWith('otpauth://totp/')
        ->and($setup->qrCodeSvg)->toContain('<svg')
        ->and(app(TwoFactorService::class)->setup($user->fresh())?->secret)->toBe($setup->secret);
});

it('confirms with a valid code and returns eight recovery codes', function () {
    Event::fake([TwoFactorAuthenticationConfirmed::class]);
    $user = enabledTwoFactorUser(confirmed: false);

    $codes = app(TwoFactorService::class)->confirm($user, currentOtp($user));

    expect($codes)->toHaveCount(8)
        ->and(app(TwoFactorService::class)->status($user->fresh()))->toBe(TwoFactorStatus::Enabled)
        ->and(app(TwoFactorService::class)->setup($user->fresh()))->toBeNull();
    Event::assertDispatched(TwoFactorAuthenticationConfirmed::class);
});

it('rejects a wrong confirmation code', function () {
    app(TwoFactorService::class)->confirm(enabledTwoFactorUser(confirmed: false), '000000');
})->throws(ValidationException::class);

it('starts over when enabled again before confirming', function () {
    $user = enabledTwoFactorUser(confirmed: false);
    $first = app(TwoFactorService::class)->setup($user);

    expect(app(TwoFactorService::class)->enable($user)->secret)->not->toBe($first?->secret);
});

it('accepts a code only once', function () {
    $user = enabledTwoFactorUser();
    $code = currentOtp($user);

    expect(app(TwoFactorService::class)->verifyCode($user, $code))->toBeTrue()
        ->and(app(TwoFactorService::class)->verifyCode($user, $code))->toBeFalse();
});

it('rejects codes while setup is pending', function () {
    $user = enabledTwoFactorUser(confirmed: false);

    expect(app(TwoFactorService::class)->verifyCode($user, currentOtp($user)))->toBeFalse();
});

it('replaces a recovery code once it is used', function () {
    Event::fake([RecoveryCodeReplaced::class]);
    $user = enabledTwoFactorUser();
    $code = $user->recoveryCodes()[0];

    expect(app(TwoFactorService::class)->useRecoveryCode($user, $code))->toBeTrue()
        ->and(app(TwoFactorService::class)->useRecoveryCode($user->fresh(), $code))->toBeFalse()
        ->and($user->fresh()->recoveryCodes())->toHaveCount(8);
    Event::assertDispatched(RecoveryCodeReplaced::class);
});

it('refuses to enable twice', function () {
    app(TwoFactorService::class)->enable(enabledTwoFactorUser());
})->throws(ValidationException::class);

it('refuses new recovery codes while disabled', function () {
    app(TwoFactorService::class)->regenerateRecoveryCodes(User::factory()->create());
})->throws(ValidationException::class);

it('regenerates recovery codes', function () {
    $user = enabledTwoFactorUser();
    $old = $user->recoveryCodes();

    $new = app(TwoFactorService::class)->regenerateRecoveryCodes($user);

    expect($new)->toHaveCount(8)->and(array_intersect($old, $new))->toBe([]);
});

it('turns two-factor authentication off', function () {
    $user = enabledTwoFactorUser();
    app(TwoFactorService::class)->disable($user);

    expect(app(TwoFactorService::class)->status($user->fresh()))->toBe(TwoFactorStatus::Disabled);
});
