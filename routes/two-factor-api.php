<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Features\TwoFactor\Http\Controllers\Api\TwoFactorChallengeController;
use Ssdev\Headless\Features\TwoFactor\TwoFactorRateLimit;

Route::post('auth/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])
    ->middleware('throttle:'.TwoFactorRateLimit::NAME)
    ->name('headless.api.auth.two-factor-challenge');
