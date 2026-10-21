<?php

namespace Ssdev\Headless\Features\Users\Events;

use Illuminate\Foundation\Auth\User;

final class UserInvited
{
    public function __construct(
        public readonly User $user,
        public readonly User $actor,
    ) {}
}
