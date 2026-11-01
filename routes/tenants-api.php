<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Features\Tenancy\Http\Controllers\Api\TenantController;
use Ssdev\Headless\Features\Tenancy\Http\Controllers\Api\TenantOwnerInvitationController;
use Ssdev\Headless\Features\Tenancy\Http\Controllers\Api\TenantSuspensionController;
use Ssdev\Headless\Features\Tenancy\Http\Middleware\EnsureCentralLevel;
use Ssdev\Headless\Features\Tenancy\OwnerInvitationRateLimit;
use Ssdev\Headless\Features\Tenancy\TenantPermission;

// The same permissions as the panel's pages: tenants.view for everything, tenants.manage for changes.
Route::middleware([EnsureCentralLevel::class, 'auth:sanctum', 'can:'.TenantPermission::View->value])
    ->prefix('tenants')
    ->group(function () {
        Route::get('/', [TenantController::class, 'index'])->name('headless.api.tenants.index');
        Route::get('{tenant}', [TenantController::class, 'show'])->whereNumber('tenant')->name('headless.api.tenants.show');

        Route::middleware('can:'.TenantPermission::Manage->value)->group(function () {
            Route::post('/', [TenantController::class, 'store'])->name('headless.api.tenants.store');
            Route::put('{tenant}', [TenantController::class, 'update'])->whereNumber('tenant')->name('headless.api.tenants.update');
            Route::delete('{tenant}', [TenantController::class, 'destroy'])->whereNumber('tenant')->name('headless.api.tenants.destroy');
            Route::post('{tenant}/suspension', [TenantSuspensionController::class, 'store'])->whereNumber('tenant')->name('headless.api.tenants.suspension.store');
            Route::delete('{tenant}/suspension', [TenantSuspensionController::class, 'destroy'])->whereNumber('tenant')->name('headless.api.tenants.suspension.destroy');
            Route::post('{tenant}/owner-invitation', [TenantOwnerInvitationController::class, 'store'])
                ->whereNumber('tenant')
                ->middleware('throttle:'.OwnerInvitationRateLimit::NAME)
                ->name('headless.api.tenants.owner-invitation.store');
        });
    });
