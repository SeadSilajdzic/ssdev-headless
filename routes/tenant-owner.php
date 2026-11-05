<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Auth\RateLimits;
use Ssdev\Headless\Features\Tenancy\Http\Controllers\AcceptOwnerInvitationController;
use Ssdev\Headless\Features\Tenancy\Http\Middleware\EnsureTenantLevel;
use Ssdev\Headless\Http\Middleware\RedirectSignedInToDashboard;

// The owner's invitation belongs to a tenant's host only.
Route::middleware([EnsureTenantLevel::class, RedirectSignedInToDashboard::class])->group(function () {
    Route::get('owner-invitation/{token}', [AcceptOwnerInvitationController::class, 'show'])->name('headless.tenant-owner.show');
    Route::post('owner-invitation', [AcceptOwnerInvitationController::class, 'store'])
        ->middleware('throttle:'.RateLimits::PASSWORD_RESET)
        ->name('headless.tenant-owner.store');
});
