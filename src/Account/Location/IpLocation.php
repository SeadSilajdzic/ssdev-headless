<?php

namespace Ssdev\Headless\Account\Location;

final readonly class IpLocation
{
    public function __construct(
        public ?string $city,
        public ?string $country,
        public float $latitude,
        public float $longitude,
    ) {}
}
