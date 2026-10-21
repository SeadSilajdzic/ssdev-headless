<?php

namespace Ssdev\Headless\Features\Users\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Ssdev\Headless\Account\PasswordResetLinkSender;
use Ssdev\Headless\Features\Users\ManagedUserRepository;
use Ssdev\Headless\Support\Flash;

final class UserPasswordResetController
{
    public function store(string $user, ManagedUserRepository $users, PasswordResetLinkSender $links): RedirectResponse
    {
        $links->send((string) $users->findOrFail($user)->getAttribute('email'));

        return back()->with(Flash::SUCCESS, __('headless::users.password_reset_sent'));
    }
}
