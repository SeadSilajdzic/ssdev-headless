<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Features\Users\Http\Controllers\Api\UserBlockController;
use Ssdev\Headless\Features\Users\Http\Controllers\Api\UserController;
use Ssdev\Headless\Features\Users\Http\Controllers\Api\UserInvitationController;
use Ssdev\Headless\Features\Users\Http\Controllers\Api\UserPasswordResetController;
use Ssdev\Headless\Features\Users\Http\Controllers\Api\UserSessionController;
use Ssdev\Headless\Features\Users\InvitationRateLimit;
use Ssdev\Headless\Features\Users\UserPermission;

Route::middleware('auth:sanctum')->prefix('users')->group(function () {
    Route::middleware('can:'.UserPermission::View->value)->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('headless.api.users.index');
        Route::get('{user}', [UserController::class, 'show'])->whereNumber('user')->name('headless.api.users.show');
    });

    Route::middleware(['can:'.UserPermission::Create->value, 'throttle:'.InvitationRateLimit::NAME])->group(function () {
        Route::post('/', [UserController::class, 'store'])->name('headless.api.users.store');
        Route::post('{user}/invitation', [UserInvitationController::class, 'store'])->whereNumber('user')->name('headless.api.users.invitation.store');
    });

    Route::middleware('can:'.UserPermission::Update->value)->group(function () {
        Route::put('{user}', [UserController::class, 'update'])->whereNumber('user')->name('headless.api.users.update');
        Route::post('{user}/password-reset', [UserPasswordResetController::class, 'store'])->whereNumber('user')->name('headless.api.users.password-reset.store');
        Route::delete('{user}/sessions', [UserSessionController::class, 'destroy'])->whereNumber('user')->name('headless.api.users.sessions.destroy');
    });

    Route::middleware('can:'.UserPermission::Block->value)->group(function () {
        Route::post('{user}/block', [UserBlockController::class, 'store'])->whereNumber('user')->name('headless.api.users.block.store');
        Route::delete('{user}/block', [UserBlockController::class, 'destroy'])->whereNumber('user')->name('headless.api.users.block.destroy');
    });

    Route::delete('{user}', [UserController::class, 'destroy'])
        ->whereNumber('user')
        ->middleware('can:'.UserPermission::Delete->value)
        ->name('headless.api.users.destroy');
});
