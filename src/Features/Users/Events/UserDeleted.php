<?php

namespace Ssdev\Headless\Features\Users\Events;

use Illuminate\Foundation\Auth\User;

final class UserDeleted
{
    public function __construct(
        public readonly int|string $userKey,
        public readonly string $email,
        public readonly User $actor,
    ) {}
}
