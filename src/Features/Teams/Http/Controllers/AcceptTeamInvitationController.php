<?php

namespace Ssdev\Headless\Features\Teams\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Inertia\Response;
use Ssdev\Headless\Account\UserRepository;
use Ssdev\Headless\Features\Teams\TeamInvitation;
use Ssdev\Headless\Features\Teams\TeamInvitationService;
use Ssdev\Headless\Http\Inertia\HeadlessPage;
use Ssdev\Headless\Support\Flash;

final class AcceptTeamInvitationController
{
    public function show(Request $request, string $invitation, UserRepository $users): Response|RedirectResponse
    {
        $pending = TeamInvitation::query()->with('team')->findOrFail($invitation);
        $team = $pending->team ?? abort(404);

        if ($request->user() === null) {
            // The address has an account, deactivated ones included: it signs in and comes back here.
            if ($users->emailTaken($pending->email)) {
                return redirect()->guest(route('login'));
            }

            return HeadlessPage::render('Teams/InvitationAccount', [
                'team' => ['name' => $team->name],
                'email' => $pending->email,
                'submitUrl' => URL::temporarySignedRoute(
                    'headless.team-invitations.account',
                    Carbon::createFromTimestamp((int) $request->query('expires')),
                    ['invitation' => $pending->getKey()],
                ),
            ]);
        }

        // A forwarded link tells another account neither the team nor the address.
        if (! $pending->isFor($request->user())) {
            return HeadlessPage::render('Teams/Invitation', ['team' => null, 'email' => null, 'acceptUrl' => null]);
        }

        return HeadlessPage::render('Teams/Invitation', [
            'team' => ['name' => $team->name],
            'email' => $pending->email,
            // The same signed address accepts it, so the signature is checked again.
            'acceptUrl' => $request->fullUrl(),
        ]);
    }

    public function store(Request $request, string $invitation, TeamInvitationService $invitations): RedirectResponse
    {
        $team = $invitations->accept($request->user(), $invitation);

        return redirect()->route('headless.teams.show', $team->getKey())->with(Flash::SUCCESS, __('headless::teams.invitation.joined', ['team' => $team->name]));
    }
}
