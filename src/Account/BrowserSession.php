<?php

namespace Ssdev\Headless\Account;

use Carbon\CarbonImmutable;
use Ssdev\Headless\Account\Location\IpLocation;

final readonly class BrowserSession
{
    public function __construct(
        public string $publicId,
        public ?string $platform,
        public ?string $browser,
        public ?string $ipAddress,
        public CarbonImmutable $lastActiveAt,
        public bool $isCurrent,
        public ?IpLocation $location,
    ) {}
}
