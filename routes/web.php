<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Http\Controllers\DashboardController;
use Ssdev\Headless\Http\Middleware\RequireVerifiedEmail;

Route::middleware(['auth', RequireVerifiedEmail::class])->group(function () {
    Route::get(DashboardController::PATH, DashboardController::class)->name('headless.dashboard');
});
