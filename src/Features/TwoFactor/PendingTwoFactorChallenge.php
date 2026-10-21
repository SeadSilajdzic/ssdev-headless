<?php

namespace Ssdev\Headless\Features\TwoFactor;

use Carbon\CarbonImmutable;

final class PendingTwoFactorChallenge
{
    public function __construct(
        public readonly string $token,
        public readonly CarbonImmutable $expiresAt,
    ) {}
}
