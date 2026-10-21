<?php

namespace Ssdev\Headless\Features\Users\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Ssdev\Headless\Features\Users\Http\Requests\AcceptInvitationRequest;
use Ssdev\Headless\Features\Users\UserInvitationService;
use Ssdev\Headless\Http\Inertia\HeadlessPage;

final class AcceptInvitationController
{
    public function show(Request $request, string $token): Response
    {
        return HeadlessPage::render('Users/AcceptInvitation', [
            'submitUrl' => route('headless.invitation.store'),
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function store(AcceptInvitationRequest $request, UserInvitationService $invitations): RedirectResponse
    {
        $invitations->accept($request->validated('token'), $request->validated('email'), $request->validated('password'));

        return redirect()->route('login')->with('status', __('headless::users.invitation.accepted'));
    }
}
