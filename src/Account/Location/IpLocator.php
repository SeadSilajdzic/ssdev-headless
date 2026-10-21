<?php

namespace Ssdev\Headless\Account\Location;

/**
 * Turns an IP address into a place for the session map; set the implementation in headless.session_locations.locator.
 */
interface IpLocator
{
    public function locate(string $ipAddress): ?IpLocation;
}
