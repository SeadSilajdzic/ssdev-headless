<?php

namespace Ssdev\Headless\Features\Teams\Http\Controllers;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\RedirectResponse;
use Ssdev\Headless\Features\Teams\Http\Requests\TransferTeamRequest;
use Ssdev\Headless\Features\Teams\TeamMembershipService;
use Ssdev\Headless\Features\Teams\TeamRepository;
use Ssdev\Headless\Support\Flash;

final class TeamOwnerController
{
    public function update(TransferTeamRequest $request, string $team, TeamRepository $teams, TeamMembershipService $memberships, Gate $gate): RedirectResponse
    {
        $model = $teams->findForMember($request->user(), $team);
        $gate->authorize('manageMembers', $model);

        $memberships->transfer($model, $model->members()->findOrFail($request->userId()));

        return back()->with(Flash::SUCCESS, __('headless::teams.transferred'));
    }
}
