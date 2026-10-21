<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Auth\RateLimits;
use Ssdev\Headless\Features\Users\Http\Controllers\AcceptInvitationController;

Route::middleware('guest')->group(function () {
    Route::get('invitation/{token}', [AcceptInvitationController::class, 'show'])->name('headless.invitation.show');
    Route::post('invitation', [AcceptInvitationController::class, 'store'])
        ->middleware('throttle:'.RateLimits::PASSWORD_RESET)
        ->name('headless.invitation.store');
});
