<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Features\Roles\Http\Controllers\RoleController;
use Ssdev\Headless\Features\Roles\RolePermission;
use Ssdev\Headless\Http\Middleware\RequireVerifiedEmail;

Route::middleware(['auth', RequireVerifiedEmail::class])->prefix('roles')->group(function () {
    Route::get('/', [RoleController::class, 'index'])
        ->middleware('can:'.RolePermission::View->value)
        ->name('headless.roles.index');

    Route::middleware('can:'.RolePermission::Manage->value)->group(function () {
        Route::get('create', [RoleController::class, 'create'])->name('headless.roles.create');
        Route::post('/', [RoleController::class, 'store'])->name('headless.roles.store');
        Route::put('{role}', [RoleController::class, 'update'])->whereNumber('role')->name('headless.roles.update');
        Route::delete('{role}', [RoleController::class, 'destroy'])->whereNumber('role')->name('headless.roles.destroy');
    });

    Route::get('{role}', [RoleController::class, 'edit'])
        ->whereNumber('role')
        ->middleware('can:'.RolePermission::View->value)
        ->name('headless.roles.edit');
});
