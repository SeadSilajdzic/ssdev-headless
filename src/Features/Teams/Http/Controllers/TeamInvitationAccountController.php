<?php

namespace Ssdev\Headless\Features\Teams\Http\Controllers;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Ssdev\Headless\Features\Teams\Http\Requests\CreateInvitedAccountRequest;
use Ssdev\Headless\Features\Teams\TeamInvitationService;
use Ssdev\Headless\Features\Teams\TeamRepository;
use Ssdev\Headless\Support\Flash;

final class TeamInvitationAccountController
{
    public function __construct(
        private TeamInvitationService $invitations,
        private TeamRepository $teams,
        private StatefulGuard $guard,
    ) {}

    public function store(CreateInvitedAccountRequest $request, string $invitation): RedirectResponse
    {
        $account = $this->invitations->createAccount($invitation, $request->name(), $request->password());

        $this->guard->login($account);
        $request->session()->regenerate();

        $team = $this->teams->findForMember($account, (int) $account->getAttribute('current_team_id'));

        return redirect()->route('headless.teams.show', $team->getKey())->with(Flash::SUCCESS, __('headless::teams.invitation.joined', ['team' => $team->name]));
    }
}
