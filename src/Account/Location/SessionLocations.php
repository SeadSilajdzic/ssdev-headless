<?php

namespace Ssdev\Headless\Account\Location;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Carbon;
use LogicException;
use Throwable;

final class SessionLocations
{
    private const CACHE_PREFIX = 'headless.session-location.';

    private ?IpLocator $locator = null;

    public function __construct(
        private Config $config,
        private Cache $cache,
        private Container $container,
    ) {}

    /**
     * Local and private addresses have no location, and a failing locator means an unknown location, never a broken page.
     */
    public function locate(?string $ipAddress): ?IpLocation
    {
        $locator = $this->locator();

        if ($locator === null || $ipAddress === null || ! $this->isPublic($ipAddress)) {
            return null;
        }

        try {
            // False marks an address the locator does not know, so it is not asked again until the entry expires.
            $location = $this->cache->remember(
                self::CACHE_PREFIX.hash('sha256', $ipAddress),
                Carbon::now()->addMinutes((int) $this->config->get('headless.session_locations.cache_minutes')),
                fn () => $locator->locate($ipAddress) ?? false,
            );
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }

        return $location instanceof IpLocation ? $location : null;
    }

    private function locator(): ?IpLocator
    {
        $class = $this->config->get('headless.session_locations.locator');

        if ($class === null) {
            return null;
        }

        if ($this->locator === null) {
            $locator = $this->container->make($class);

            if (! $locator instanceof IpLocator) {
                throw new LogicException("[{$class}] must implement ".IpLocator::class.'.');
            }

            $this->locator = $locator;
        }

        return $this->locator;
    }

    private function isPublic(string $ipAddress): bool
    {
        return filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
