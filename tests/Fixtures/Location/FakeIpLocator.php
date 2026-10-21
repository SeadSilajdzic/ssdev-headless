<?php

namespace Ssdev\Headless\Tests\Fixtures\Location;

use RuntimeException;
use Ssdev\Headless\Account\Location\IpLocation;
use Ssdev\Headless\Account\Location\IpLocator;

final class FakeIpLocator implements IpLocator
{
    public static int $calls = 0;

    public static bool $throws = false;

    public static function reset(): void
    {
        self::$calls = 0;
        self::$throws = false;
    }

    public function locate(string $ipAddress): ?IpLocation
    {
        self::$calls++;

        if (self::$throws) {
            throw new RuntimeException('The location database is missing.');
        }

        return match ($ipAddress) {
            '203.0.113.7' => new IpLocation('Sarajevo', 'Bosnia and Herzegovina', 43.85, 18.41),
            '198.51.100.4' => new IpLocation('Berlin', 'Germany', 52.52, 13.40),
            default => null,
        };
    }
}
