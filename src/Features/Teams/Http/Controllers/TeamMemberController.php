<?php

namespace Ssdev\Headless\Features\Teams\Http\Controllers;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Ssdev\Headless\Features\Teams\TeamMembershipService;
use Ssdev\Headless\Features\Teams\TeamRepository;
use Ssdev\Headless\Support\Flash;

final class TeamMemberController
{
    public function __construct(
        private TeamRepository $teams,
        private TeamMembershipService $memberships,
    ) {}

    public function destroy(Request $request, string $team, string $user, Gate $gate): RedirectResponse
    {
        $model = $this->teams->findForMember($request->user(), $team);
        $gate->authorize('manageMembers', $model);

        $this->memberships->remove($model, $model->members()->findOrFail($user));

        return back()->with(Flash::SUCCESS, __('headless::teams.member_removed'));
    }

    public function leave(Request $request, string $team): RedirectResponse
    {
        $this->memberships->leave($request->user(), $this->teams->findForMember($request->user(), $team));

        return redirect()->route('headless.dashboard')->with(Flash::SUCCESS, __('headless::teams.left'));
    }
}
