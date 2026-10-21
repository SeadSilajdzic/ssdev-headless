<?php

namespace Ssdev\Headless\Features\Users\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Ssdev\Headless\Features\Users\AccessRevoker;
use Ssdev\Headless\Features\Users\ManagedUserRepository;
use Ssdev\Headless\Support\Flash;

final class UserSessionController
{
    public function destroy(string $user, ManagedUserRepository $users, AccessRevoker $access): RedirectResponse
    {
        $access->endSessions($users->findOrFail($user));

        return back()->with(Flash::SUCCESS, __('headless::users.sessions_ended'));
    }
}
