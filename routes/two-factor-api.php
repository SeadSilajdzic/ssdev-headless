<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Auth\RateLimits;
use Ssdev\Headless\Features\TwoFactor\Http\Controllers\Api\TwoFactorChallengeController;
use Ssdev\Headless\Features\TwoFactor\Http\Controllers\Api\TwoFactorController;
use Ssdev\Headless\Features\TwoFactor\TwoFactorRateLimit;

Route::post('auth/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])
    ->middleware('throttle:'.TwoFactorRateLimit::NAME)
    ->name('headless.api.auth.two-factor-challenge');

Route::middleware('auth:sanctum')->prefix('me/two-factor')->group(function () {
    Route::get('/', [TwoFactorController::class, 'show'])->name('headless.api.two-factor.show');
    Route::post('/', [TwoFactorController::class, 'enable'])
        ->middleware('throttle:'.RateLimits::REAUTHENTICATION)
        ->name('headless.api.two-factor.enable');
    Route::post('confirm', [TwoFactorController::class, 'confirm'])
        ->middleware('throttle:'.TwoFactorRateLimit::NAME)
        ->name('headless.api.two-factor.confirm');
    Route::post('recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])
        ->middleware('throttle:'.RateLimits::REAUTHENTICATION)
        ->name('headless.api.two-factor.recovery-codes');
    Route::delete('/', [TwoFactorController::class, 'disable'])
        ->middleware('throttle:'.RateLimits::REAUTHENTICATION)
        ->name('headless.api.two-factor.disable');
});
