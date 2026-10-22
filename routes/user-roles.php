<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Features\Roles\RolePermission;
use Ssdev\Headless\Features\Users\Http\Controllers\UserRoleController;
use Ssdev\Headless\Features\Users\UserPermission;
use Ssdev\Headless\Http\Middleware\RequireVerifiedEmail;

Route::middleware(['auth', RequireVerifiedEmail::class, 'password.confirm', 'can:'.UserPermission::View->value, 'can:'.RolePermission::Manage->value])
    ->put('users/{user}/roles', [UserRoleController::class, 'update'])
    ->whereNumber('user')
    ->name('headless.users.roles.update');
