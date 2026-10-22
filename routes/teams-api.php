<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Features\Teams\Http\Controllers\Api\TeamController;
use Ssdev\Headless\Features\Teams\Http\Controllers\Api\TeamInvitationController;
use Ssdev\Headless\Features\Teams\Http\Controllers\Api\TeamMemberController;
use Ssdev\Headless\Features\Teams\TeamInvitationRateLimit;

Route::middleware('auth:sanctum')->group(function () {
    Route::put('me/current-team', [TeamController::class, 'switch'])->name('headless.api.current-team.update');
    Route::post('team-invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->whereNumber('invitation')->name('headless.api.team-invitations.accept');

    Route::prefix('teams')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('headless.api.teams.index');
        Route::post('/', [TeamController::class, 'store'])->name('headless.api.teams.store');

        Route::whereNumber(['team', 'user', 'invitation'])->group(function () {
            Route::get('{team}', [TeamController::class, 'show'])->name('headless.api.teams.show');
            Route::put('{team}', [TeamController::class, 'update'])->name('headless.api.teams.update');
            Route::delete('{team}', [TeamController::class, 'destroy'])->name('headless.api.teams.destroy');
            Route::get('{team}/members', [TeamMemberController::class, 'index'])->name('headless.api.teams.members.index');
            Route::delete('{team}/members/{user}', [TeamMemberController::class, 'destroy'])->name('headless.api.teams.members.destroy');
            Route::delete('{team}/membership', [TeamMemberController::class, 'leave'])->name('headless.api.teams.membership.destroy');
            Route::put('{team}/owner', [TeamMemberController::class, 'transfer'])->name('headless.api.teams.owner.update');
            Route::get('{team}/invitations', [TeamInvitationController::class, 'index'])->name('headless.api.teams.invitations.index');
            Route::post('{team}/invitations', [TeamInvitationController::class, 'store'])
                ->middleware('throttle:'.TeamInvitationRateLimit::NAME)
                ->name('headless.api.teams.invitations.store');
            Route::delete('{team}/invitations/{invitation}', [TeamInvitationController::class, 'destroy'])->name('headless.api.teams.invitations.destroy');
        });
    });
});
