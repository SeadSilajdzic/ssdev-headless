<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Features\Tenancy\Http\Controllers\TenantController;
use Ssdev\Headless\Features\Tenancy\Http\Controllers\TenantOwnerInvitationController;
use Ssdev\Headless\Features\Tenancy\Http\Controllers\TenantSuspensionController;
use Ssdev\Headless\Features\Tenancy\Http\Middleware\EnsureCentralLevel;
use Ssdev\Headless\Features\Tenancy\OwnerInvitationRateLimit;
use Ssdev\Headless\Features\Tenancy\TenantPermission;
use Ssdev\Headless\Http\Middleware\RequireVerifiedEmail;

// Every change lands back on the tenants pages, so each one needs tenants.view as well.
Route::middleware([EnsureCentralLevel::class, 'auth', RequireVerifiedEmail::class, 'password.confirm', 'can:'.TenantPermission::View->value])
    ->prefix('tenants')
    ->group(function () {
        Route::get('/', [TenantController::class, 'index'])->name('headless.tenants.index');
        Route::get('{tenant}', [TenantController::class, 'show'])->whereNumber('tenant')->name('headless.tenants.show');

        Route::middleware('can:'.TenantPermission::Manage->value)->group(function () {
            Route::get('create', [TenantController::class, 'create'])->name('headless.tenants.create');
            Route::post('/', [TenantController::class, 'store'])->name('headless.tenants.store');
            Route::put('{tenant}', [TenantController::class, 'update'])->whereNumber('tenant')->name('headless.tenants.update');
            Route::delete('{tenant}', [TenantController::class, 'destroy'])->whereNumber('tenant')->name('headless.tenants.destroy');
            Route::post('{tenant}/suspension', [TenantSuspensionController::class, 'store'])->whereNumber('tenant')->name('headless.tenants.suspension.store');
            Route::delete('{tenant}/suspension', [TenantSuspensionController::class, 'destroy'])->whereNumber('tenant')->name('headless.tenants.suspension.destroy');
            Route::post('{tenant}/owner-invitation', [TenantOwnerInvitationController::class, 'store'])
                ->whereNumber('tenant')
                ->middleware('throttle:'.OwnerInvitationRateLimit::NAME)
                ->name('headless.tenants.owner-invitation.store');
        });
    });
