<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Features\Teams\Http\Controllers\TeamMemberRoleController;
use Ssdev\Headless\Http\Middleware\RequireVerifiedEmail;

Route::middleware(['auth', RequireVerifiedEmail::class])
    ->put('teams/{team}/members/{user}/roles', [TeamMemberRoleController::class, 'update'])
    ->whereNumber(['team', 'user'])
    ->name('headless.teams.members.roles.update');
