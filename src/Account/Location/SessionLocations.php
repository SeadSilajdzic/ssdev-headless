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

    private const IPV4_MAPPED_PREFIX = "\0\0\0\0\0\0\0\0\0\0\xff\xff";

    private ?IpLocator $locator = null;

    // A broken locator (e.g. a missing database) is asked once per request, not once per session.
    private bool $locatorFailed = false;

    public function __construct(
        private Config $config,
        private Cache $cache,
        private Container $container,
    ) {}

    // Local and private addresses have no location; a failing locator means an unknown location, never a broken page.
    public function locate(?string $ipAddress): ?IpLocation
    {
        $locator = $this->locator();

        if ($locator === null || $this->locatorFailed || $ipAddress === null) {
            return null;
        }

        $ipAddress = $this->withoutIpv6Mapping($ipAddress);

        if (! $this->isPublic($ipAddress)) {
            return null;
        }

        try {
            $cached = $this->cache->remember(
                self::CACHE_PREFIX.hash('sha256', $ipAddress),
                Carbon::now()->addMinutes((int) $this->config->get('headless.session_locations.cache_minutes')),
                fn () => $this->cacheValue($locator->locate($ipAddress)),
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->locatorFailed = true;

            return null;
        }

        return is_array($cached) ? new IpLocation(...$cached) : null;
    }

    /**
     * Plain values, as applications may forbid objects in the cache; false remembers an address the locator does not know.
     *
     * @return array{?string, ?string, float, float}|false
     */
    private function cacheValue(?IpLocation $location): array|false
    {
        return $location === null ? false : [$location->city, $location->country, $location->latitude, $location->longitude];
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

    // On a dual-stack server an IPv4 client arrives as ::ffff:a.b.c.d, which PHP counts as reserved.
    private function withoutIpv6Mapping(string $ipAddress): string
    {
        $packed = filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false ? false : inet_pton($ipAddress);

        return $packed !== false && str_starts_with($packed, self::IPV4_MAPPED_PREFIX)
            ? (string) inet_ntop(substr($packed, strlen(self::IPV4_MAPPED_PREFIX)))
            : $ipAddress;
    }

    private function isPublic(string $ipAddress): bool
    {
        return filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
