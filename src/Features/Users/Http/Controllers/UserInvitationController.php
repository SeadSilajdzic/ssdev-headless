<?php

namespace Ssdev\Headless\Features\Users\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Ssdev\Headless\Features\Users\ManagedUserRepository;
use Ssdev\Headless\Features\Users\UserInvitationService;
use Ssdev\Headless\Support\Flash;

final class UserInvitationController
{
    public function store(string $user, ManagedUserRepository $users, UserInvitationService $invitations): RedirectResponse
    {
        $invitations->resend($users->findOrFail($user));

        return back()->with(Flash::SUCCESS, __('headless::users.invitation_sent'));
    }
}
