<?php

namespace Ssdev\Headless\Features\Users\Http\Controllers\Api;

use Illuminate\Http\Response;
use Ssdev\Headless\Features\Users\AccessRevoker;
use Ssdev\Headless\Features\Users\ManagedUserRepository;

final class UserSessionController
{
    public function destroy(string $user, ManagedUserRepository $users, AccessRevoker $access): Response
    {
        $access->signOutEverywhere($users->findOrFail($user));

        return response()->noContent();
    }
}
