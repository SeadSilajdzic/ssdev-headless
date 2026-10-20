<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController;
use Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;
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

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware(['throttle:'.RateLimits::PASSWORD_RESET, RejectHoneypotSubmissions::class])
        ->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:'.RateLimits::PASSWORD_RESET)
        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('email/verify', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:'.RateLimits::VERIFICATION])
        ->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:'.RateLimits::VERIFICATION)
        ->name('verification.send');

    Route::get('user/confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('user/confirm-password', [ConfirmablePasswordController::class, 'store'])
        ->middleware('throttle:'.RateLimits::REAUTHENTICATION)
        ->name('password.confirm.store');
    Route::get('user/confirmed-password-status', [ConfirmedPasswordStatusController::class, 'show'])->name('password.confirmation');
});
