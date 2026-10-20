<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Account\EmailChangeService;
use Ssdev\Headless\Auth\RateLimits;
use Ssdev\Headless\Http\Controllers\Settings\AvatarController;
use Ssdev\Headless\Http\Controllers\Settings\EmailChangeController;
use Ssdev\Headless\Http\Controllers\Settings\ProfileController;

// Settings stay reachable without a verified email, so a mistyped address can still be fixed.
Route::middleware('auth')->prefix('settings')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('headless.profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])
        ->middleware('throttle:'.RateLimits::REAUTHENTICATION)
        ->name('headless.profile.update');

    Route::post('profile/avatar', [AvatarController::class, 'store'])->name('headless.avatar.store');
    Route::delete('profile/avatar', [AvatarController::class, 'destroy'])->name('headless.avatar.destroy');
});

// Opened from the new mailbox, possibly on another device, so it relies on the signature instead of a session.
Route::get('settings/email/confirm/{user}', EmailChangeController::class)
    ->middleware(['signed', 'throttle:'.RateLimits::EMAIL_CHANGE])
    ->name(EmailChangeService::CONFIRM_ROUTE);
