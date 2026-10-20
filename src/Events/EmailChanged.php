<?php

namespace Ssdev\Headless\Events;

use Illuminate\Foundation\Auth\User;

final class EmailChanged
{
    public function __construct(
        public readonly User $user,
        public readonly string $oldEmail,
        public readonly string $newEmail,
    ) {}
}
