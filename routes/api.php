<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Auth\RateLimits;
use Ssdev\Headless\Http\Controllers\Api\AccountController;
use Ssdev\Headless\Http\Controllers\Api\ApiTokenController;
use Ssdev\Headless\Http\Controllers\Api\AuthTokenController;
use Ssdev\Headless\Http\Controllers\Api\AvatarController;
use Ssdev\Headless\Http\Controllers\Api\EmailVerificationController;
use Ssdev\Headless\Http\Controllers\Api\PasswordController;
use Ssdev\Headless\Http\Controllers\Api\ProfileController;

Route::post('auth/token', [AuthTokenController::class, 'store'])
    ->middleware('throttle:'.RateLimits::LOGIN)
    ->name('headless.api.auth.token');

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('auth/token', [AuthTokenController::class, 'destroy'])->name('headless.api.auth.logout');

    Route::get('me', [ProfileController::class, 'show'])->name('headless.api.me');
    Route::put('me', [ProfileController::class, 'update'])
        ->middleware('throttle:'.RateLimits::REAUTHENTICATION)
        ->name('headless.api.me.update');
    Route::delete('me', [AccountController::class, 'destroy'])
        ->middleware('throttle:'.RateLimits::REAUTHENTICATION)
        ->name('headless.api.me.destroy');
    Route::put('me/password', [PasswordController::class, 'update'])
        ->middleware('throttle:'.RateLimits::REAUTHENTICATION)
        ->name('headless.api.me.password');
    Route::post('me/avatar', [AvatarController::class, 'store'])->name('headless.api.me.avatar.store');
    Route::delete('me/avatar', [AvatarController::class, 'destroy'])->name('headless.api.me.avatar.destroy');
    Route::post('me/email/verification-notification', [EmailVerificationController::class, 'store'])
        ->middleware('throttle:'.RateLimits::VERIFICATION)
        ->name('headless.api.me.verification');

    Route::get('tokens', [ApiTokenController::class, 'index'])->name('headless.api.tokens.index');
    Route::post('tokens', [ApiTokenController::class, 'store'])
        ->middleware('throttle:'.RateLimits::REAUTHENTICATION)
        ->name('headless.api.tokens.store');
    Route::delete('tokens/{token}', [ApiTokenController::class, 'destroy'])->name('headless.api.tokens.destroy');
});

Route::fallback(fn () => abort(404))->name('headless.api.fallback');
