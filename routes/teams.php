<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Features\Teams\Http\Controllers\AcceptTeamInvitationController;

Route::middleware(['auth', 'signed'])->prefix('team-invitations')->group(function () {
    Route::get('{invitation}', [AcceptTeamInvitationController::class, 'show'])->whereNumber('invitation')->name('headless.team-invitations.show');
    Route::post('{invitation}', [AcceptTeamInvitationController::class, 'store'])->whereNumber('invitation')->name('headless.team-invitations.store');
});
