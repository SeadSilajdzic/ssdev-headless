<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Auth\RateLimits;
use Ssdev\Headless\Features\TwoFactor\Http\Controllers\TwoFactorSettingsController;
use Ssdev\Headless\Features\TwoFactor\TwoFactorRateLimit;

Route::middleware(['auth', 'password.confirm'])->prefix('settings/two-factor')->group(function () {
    Route::get('/', [TwoFactorSettingsController::class, 'edit'])->name('headless.two-factor.edit');
    Route::post('/', [TwoFactorSettingsController::class, 'enable'])
        ->middleware('throttle:'.RateLimits::REAUTHENTICATION)
        ->name('headless.two-factor.enable');
    Route::post('confirm', [TwoFactorSettingsController::class, 'confirm'])
        ->middleware('throttle:'.TwoFactorRateLimit::NAME)
        ->name('headless.two-factor.confirm');
    Route::post('recovery-codes', [TwoFactorSettingsController::class, 'regenerateRecoveryCodes'])
        ->middleware('throttle:'.RateLimits::REAUTHENTICATION)
        ->name('headless.two-factor.recovery-codes');
    Route::delete('/', [TwoFactorSettingsController::class, 'disable'])
        ->middleware('throttle:'.RateLimits::REAUTHENTICATION)
        ->name('headless.two-factor.disable');
});
