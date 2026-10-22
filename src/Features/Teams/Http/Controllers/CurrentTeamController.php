<?php

namespace Ssdev\Headless\Features\Teams\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Ssdev\Headless\Features\Teams\Http\Requests\SwitchTeamRequest;
use Ssdev\Headless\Features\Teams\TeamMembershipService;
use Ssdev\Headless\Features\Teams\TeamRepository;

final class CurrentTeamController
{
    public function update(SwitchTeamRequest $request, TeamRepository $teams, TeamMembershipService $memberships): RedirectResponse
    {
        $memberships->switchTo($request->user(), $teams->findForMember($request->user(), $request->teamId()));

        return back();
    }
}
