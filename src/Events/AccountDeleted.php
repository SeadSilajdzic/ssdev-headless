<?php

namespace Ssdev\Headless\Events;

final class AccountDeleted
{
    public function __construct(
        public readonly int|string $userKey,
        public readonly string $email,
    ) {}
}
