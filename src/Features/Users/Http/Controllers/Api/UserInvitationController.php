<?php

namespace Ssdev\Headless\Features\Users\Http\Controllers\Api;

use Illuminate\Http\Response;
use Ssdev\Headless\Features\Users\ManagedUserRepository;
use Ssdev\Headless\Features\Users\UserInvitationService;

final class UserInvitationController
{
    public function store(string $user, ManagedUserRepository $users, UserInvitationService $invitations): Response
    {
        $invitations->resend($users->findOrFail($user));

        return response()->noContent();
    }
}
