<?php

namespace Ssdev\Headless\Features\Tenancy\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Ssdev\Headless\Features\Tenancy\Http\Requests\AcceptOwnerInvitationRequest;
use Ssdev\Headless\Features\Tenancy\TenantOwnerService;
use Ssdev\Headless\Http\Inertia\HeadlessPage;

final class AcceptOwnerInvitationController
{
    public function show(Request $request, string $token): Response
    {
        return HeadlessPage::render('Tenancy/AcceptOwnerInvitation', [
            'submitUrl' => route('headless.tenant-owner.store'),
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function store(AcceptOwnerInvitationRequest $request, TenantOwnerService $owners): RedirectResponse
    {
        $owners->accept($request->token(), $request->email(), $request->name(), $request->password());

        return redirect()->route('login')->with('status', __('headless::tenancy.owner_invitation.accepted'));
    }
}
