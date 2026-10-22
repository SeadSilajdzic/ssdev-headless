<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Features\Teams\Http\Controllers\Api\TeamMemberRoleController;

Route::middleware('auth:sanctum')
    ->put('teams/{team}/members/{user}/roles', [TeamMemberRoleController::class, 'update'])
    ->whereNumber(['team', 'user'])
    ->name('headless.api.teams.members.roles.update');
