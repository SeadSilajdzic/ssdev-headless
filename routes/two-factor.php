<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;
use Ssdev\Headless\Auth\RateLimits;
use Ssdev\Headless\Features\TwoFactor\Http\Controllers\TwoFactorSettingsController;
use Ssdev\Headless\Features\TwoFactor\TwoFactorRateLimit;

// Fortify's login pipeline redirects to the route named two-factor.login.
Route::middleware('guest')->group(function () {
    Route::get('two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'create'])->name('two-factor.login');
    Route::post('two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:'.TwoFactorRateLimit::NAME)
        ->name('two-factor.login.store');
});

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
