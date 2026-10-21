<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Auth\RateLimits;
use Ssdev\Headless\Http\Controllers\Api\AuthTokenController;

Route::post('auth/token', [AuthTokenController::class, 'store'])
    ->middleware('throttle:'.RateLimits::LOGIN)
    ->name('headless.api.auth.token');

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('auth/token', [AuthTokenController::class, 'destroy'])->name('headless.api.auth.logout');
});

Route::fallback(fn () => abort(404))->name('headless.api.fallback');
