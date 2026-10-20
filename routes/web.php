<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Http\Controllers\DashboardController;

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('headless.dashboard');
});
