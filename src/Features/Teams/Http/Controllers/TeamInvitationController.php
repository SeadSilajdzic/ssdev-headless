<?php

namespace Ssdev\Headless\Features\Teams\Http\Controllers;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Ssdev\Headless\Features\Teams\Http\Requests\InviteTeamMemberRequest;
use Ssdev\Headless\Features\Teams\TeamInvitation;
use Ssdev\Headless\Features\Teams\TeamInvitationService;
use Ssdev\Headless\Features\Teams\TeamRepository;
use Ssdev\Headless\Support\Flash;

final class TeamInvitationController
{
    public function __construct(
        private TeamRepository $teams,
        private TeamInvitationService $invitations,
        private Gate $gate,
    ) {}

    public function store(InviteTeamMemberRequest $request, string $team): RedirectResponse
    {
        $model = $this->teams->findForMember($request->user(), $team);
        $this->gate->authorize('manageMembers', $model);

        $this->invitations->invite($request->user(), $model, $request->email(), $request->roleId());

        return back()->with(Flash::SUCCESS, __('headless::teams.invited'));
    }

    public function destroy(Request $request, string $team, string $invitation): RedirectResponse
    {
        $model = $this->teams->findForMember($request->user(), $team);
        $this->gate->authorize('manageMembers', $model);

        $this->invitations->cancel($model, TeamInvitation::query()->findOrFail($invitation));

        return back()->with(Flash::SUCCESS, __('headless::teams.invitation_cancelled'));
    }
}
