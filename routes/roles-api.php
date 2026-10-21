<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Features\Roles\Http\Controllers\Api\PermissionController;
use Ssdev\Headless\Features\Roles\Http\Controllers\Api\RoleController;
use Ssdev\Headless\Features\Roles\Http\Controllers\Api\RoleMemberController;
use Ssdev\Headless\Features\Roles\RolePermission;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('permissions', [PermissionController::class, 'index'])->name('headless.api.permissions.index');

    Route::prefix('roles')->group(function () {
        Route::middleware('can:'.RolePermission::View->value)->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('headless.api.roles.index');
            Route::get('{role}', [RoleController::class, 'show'])->whereNumber('role')->name('headless.api.roles.show');
            Route::get('{role}/members', [RoleMemberController::class, 'index'])->whereNumber('role')->name('headless.api.roles.members.index');
        });

        Route::middleware('can:'.RolePermission::Manage->value)->group(function () {
            Route::post('/', [RoleController::class, 'store'])->name('headless.api.roles.store');
            Route::put('{role}', [RoleController::class, 'update'])->whereNumber('role')->name('headless.api.roles.update');
            Route::delete('{role}', [RoleController::class, 'destroy'])->whereNumber('role')->name('headless.api.roles.destroy');
            Route::post('{role}/members', [RoleMemberController::class, 'store'])->whereNumber('role')->name('headless.api.roles.members.store');
            Route::delete('{role}/members/{user}', [RoleMemberController::class, 'destroy'])
                ->whereNumber(['role', 'user'])
                ->name('headless.api.roles.members.destroy');
        });
    });
});
