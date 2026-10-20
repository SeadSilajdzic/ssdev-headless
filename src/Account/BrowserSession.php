<?php

namespace Ssdev\Headless\Account;

use Carbon\CarbonImmutable;

final readonly class BrowserSession
{
    public function __construct(
        public string $publicId,
        public ?string $platform,
        public ?string $browser,
        public ?string $ipAddress,
        public CarbonImmutable $lastActiveAt,
        public bool $isCurrent,
    ) {}
}
