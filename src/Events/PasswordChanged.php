<?php

namespace Ssdev\Headless\Events;

use Illuminate\Foundation\Auth\User;

final class PasswordChanged
{
    public function __construct(public readonly User $user) {}
}
