<?php

namespace Ssdev\Headless\Account\Location;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Contracts\Config\Repository;
use LogicException;

/**
 * Reads a local MaxMind GeoLite2 City database, so no IP address leaves the server.
 */
final class MaxMindLocator implements IpLocator
{
    private ?Reader $reader = null;

    public function __construct(private Repository $config) {}

    public function locate(string $ipAddress): ?IpLocation
    {
        try {
            $record = $this->reader()->city($ipAddress);
        } catch (AddressNotFoundException) {
            return null;
        }

        if ($record->location->latitude === null || $record->location->longitude === null) {
            return null;
        }

        return new IpLocation($record->city->name, $record->country->name, $record->location->latitude, $record->location->longitude);
    }

    private function reader(): Reader
    {
        if (! class_exists(Reader::class)) {
            throw new LogicException('Run composer require geoip2/geoip2 to use '.self::class.'.');
        }

        return $this->reader ??= new Reader((string) $this->config->get('headless.session_locations.maxmind_database'));
    }
}
