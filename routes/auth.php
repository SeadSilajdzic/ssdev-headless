<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Ssdev\Headless\Auth\RateLimits;
use Ssdev\Headless\Http\Controllers\Auth\RegistrationController;
use Ssdev\Headless\Http\Middleware\EnsureRegistrationIsEnabled;
use Ssdev\Headless\Http\Middleware\RejectHoneypotSubmissions;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware(['throttle:'.RateLimits::LOGIN, RejectHoneypotSubmissions::class])
        ->name('login.store');

    Route::middleware(EnsureRegistrationIsEnabled::class)->group(function () {
        Route::get('register', [RegistrationController::class, 'create'])->name('register');
        Route::post('register', [RegistrationController::class, 'store'])
            ->middleware(['throttle:'.RateLimits::REGISTRATION, RejectHoneypotSubmissions::class])
            ->name('register.store');
    });
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
