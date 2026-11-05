<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Auth\RateLimits;
use Ssdev\Headless\Features\Users\Http\Controllers\AcceptInvitationController;
use Ssdev\Headless\Features\Users\Http\Controllers\UserBlockController;
use Ssdev\Headless\Features\Users\Http\Controllers\UserController;
use Ssdev\Headless\Features\Users\Http\Controllers\UserInvitationController;
use Ssdev\Headless\Features\Users\Http\Controllers\UserPasswordResetController;
use Ssdev\Headless\Features\Users\Http\Controllers\UserSessionController;
use Ssdev\Headless\Features\Users\InvitationRateLimit;
use Ssdev\Headless\Features\Users\UserPermission;
use Ssdev\Headless\Http\Middleware\RedirectSignedInToDashboard;
use Ssdev\Headless\Http\Middleware\RequireVerifiedEmail;

Route::middleware(RedirectSignedInToDashboard::class)->group(function () {
    Route::get('invitation/{token}', [AcceptInvitationController::class, 'show'])->name('headless.invitation.show');
    Route::post('invitation', [AcceptInvitationController::class, 'store'])
        ->middleware('throttle:'.RateLimits::PASSWORD_RESET)
        ->name('headless.invitation.store');
});

// Every change lands back on the users pages, so each one needs users.view as well.
$canView = 'can:'.UserPermission::View->value;

Route::middleware(['auth', RequireVerifiedEmail::class, 'password.confirm'])->prefix('users')->group(function () use ($canView) {
    Route::get('/', [UserController::class, 'index'])->middleware($canView)->name('headless.users.index');

    Route::middleware([$canView, 'can:'.UserPermission::Create->value])->group(function () {
        Route::get('create', [UserController::class, 'create'])->name('headless.users.create');
        Route::post('/', [UserController::class, 'store'])
            ->middleware('throttle:'.InvitationRateLimit::NAME)
            ->name('headless.users.store');
        Route::post('{user}/invitation', [UserInvitationController::class, 'store'])
            ->whereNumber('user')
            ->middleware('throttle:'.InvitationRateLimit::NAME)
            ->name('headless.users.invitation.store');
    });

    Route::get('{user}', [UserController::class, 'show'])->whereNumber('user')->middleware($canView)->name('headless.users.show');

    Route::middleware([$canView, 'can:'.UserPermission::Update->value])->group(function () {
        Route::put('{user}', [UserController::class, 'update'])->whereNumber('user')->name('headless.users.update');
        Route::post('{user}/password-reset', [UserPasswordResetController::class, 'store'])->whereNumber('user')->name('headless.users.password-reset.store');
        Route::delete('{user}/sessions', [UserSessionController::class, 'destroy'])->whereNumber('user')->name('headless.users.sessions.destroy');
    });

    Route::middleware([$canView, 'can:'.UserPermission::Block->value])->group(function () {
        Route::post('{user}/block', [UserBlockController::class, 'store'])->whereNumber('user')->name('headless.users.block.store');
        Route::delete('{user}/block', [UserBlockController::class, 'destroy'])->whereNumber('user')->name('headless.users.block.destroy');
    });

    Route::delete('{user}', [UserController::class, 'destroy'])
        ->whereNumber('user')
        ->middleware([$canView, 'can:'.UserPermission::Delete->value])
        ->name('headless.users.destroy');
});
