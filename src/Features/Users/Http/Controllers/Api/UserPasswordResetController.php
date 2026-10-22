<?php

namespace Ssdev\Headless\Features\Users\Http\Controllers\Api;

use Illuminate\Http\Response;
use Ssdev\Headless\Account\PasswordResetLinkSender;
use Ssdev\Headless\Features\Users\ManagedUserRepository;

final class UserPasswordResetController
{
    public function store(string $user, ManagedUserRepository $users, PasswordResetLinkSender $links): Response
    {
        $links->send((string) $users->findOrFail($user)->getAttribute('email'));

        return response()->noContent();
    }
}
