<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Auth\RateLimits;
use Ssdev\Headless\Features\Tenancy\Http\Controllers\AcceptOwnerInvitationController;

Route::middleware('guest')->group(function () {
    Route::get('owner-invitation/{token}', [AcceptOwnerInvitationController::class, 'show'])->name('headless.tenant-owner.show');
    Route::post('owner-invitation', [AcceptOwnerInvitationController::class, 'store'])
        ->middleware('throttle:'.RateLimits::PASSWORD_RESET)
        ->name('headless.tenant-owner.store');
});
