<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Auth\RateLimits;
use Ssdev\Headless\Features\Teams\Http\Controllers\AcceptTeamInvitationController;
use Ssdev\Headless\Features\Teams\Http\Controllers\CurrentTeamController;
use Ssdev\Headless\Features\Teams\Http\Controllers\TeamController;
use Ssdev\Headless\Features\Teams\Http\Controllers\TeamInvitationAccountController;
use Ssdev\Headless\Features\Teams\Http\Controllers\TeamInvitationController;
use Ssdev\Headless\Features\Teams\Http\Controllers\TeamMemberController;
use Ssdev\Headless\Features\Teams\Http\Controllers\TeamOwnerController;
use Ssdev\Headless\Features\Teams\TeamInvitationRateLimit;
use Ssdev\Headless\Http\Middleware\RequireVerifiedEmail;

// Guests open the link too: one with an account signs in first, one without creates it, as the mailed link proves the address.
Route::middleware('signed')->prefix('team-invitations')->whereNumber('invitation')->group(function () {
    Route::get('{invitation}', [AcceptTeamInvitationController::class, 'show'])
        ->middleware(RequireVerifiedEmail::class.':'.RequireVerifiedEmail::SIGNED_IN)
        ->name('headless.team-invitations.show');
    Route::post('{invitation}', [AcceptTeamInvitationController::class, 'store'])
        ->middleware(['auth', RequireVerifiedEmail::class])
        ->name('headless.team-invitations.store');
    Route::post('{invitation}/account', [TeamInvitationAccountController::class, 'store'])
        ->middleware(['guest', 'throttle:'.RateLimits::REGISTRATION])
        ->name('headless.team-invitations.account');
});

Route::middleware(['auth', RequireVerifiedEmail::class])->group(function () {
    Route::put('current-team', [CurrentTeamController::class, 'update'])->name('headless.current-team.update');

    Route::prefix('teams')->group(function () {
        Route::get('create', [TeamController::class, 'create'])->name('headless.teams.create');
        Route::post('/', [TeamController::class, 'store'])->name('headless.teams.store');

        Route::whereNumber(['team', 'user', 'invitation'])->group(function () {
            Route::get('{team}', [TeamController::class, 'show'])->name('headless.teams.show');
            Route::put('{team}', [TeamController::class, 'update'])->name('headless.teams.update');
            Route::delete('{team}', [TeamController::class, 'destroy'])->name('headless.teams.destroy');
            Route::delete('{team}/membership', [TeamMemberController::class, 'leave'])->name('headless.teams.membership.destroy');
            Route::delete('{team}/members/{user}', [TeamMemberController::class, 'destroy'])->name('headless.teams.members.destroy');
            Route::put('{team}/owner', [TeamOwnerController::class, 'update'])->name('headless.teams.owner.update');
            Route::post('{team}/invitations', [TeamInvitationController::class, 'store'])
                ->middleware('throttle:'.TeamInvitationRateLimit::NAME)
                ->name('headless.teams.invitations.store');
            Route::delete('{team}/invitations/{invitation}', [TeamInvitationController::class, 'destroy'])->name('headless.teams.invitations.destroy');
        });
    });
});
