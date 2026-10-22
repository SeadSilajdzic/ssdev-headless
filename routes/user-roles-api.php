<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Features\Roles\RolePermission;
use Ssdev\Headless\Features\Users\Http\Controllers\Api\UserRoleController;
use Ssdev\Headless\Features\Users\UserPermission;

Route::middleware(['auth:sanctum', 'can:'.UserPermission::View->value])->prefix('users/{user}/roles')->whereNumber('user')->group(function () {
    Route::get('/', [UserRoleController::class, 'index'])
        ->middleware('can:'.RolePermission::View->value)
        ->name('headless.api.users.roles.index');
    Route::put('/', [UserRoleController::class, 'update'])
        ->middleware('can:'.RolePermission::Manage->value)
        ->name('headless.api.users.roles.update');
});
