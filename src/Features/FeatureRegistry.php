<?php

namespace Ssdev\Headless\Features;

final class FeatureRegistry
{
    /** @var array<string, FeatureModule> */
    private array $available = [];

    /** @var list<string> */
    private array $enabled;

    /**
     * @param  iterable<FeatureModule>  $available
     * @param  list<string>  $enabled
     */
    public function __construct(iterable $available, array $enabled)
    {
        foreach ($available as $module) {
            $this->available[$module->key()] = $module;
        }

        foreach ($enabled as $key) {
            if (! isset($this->available[$key])) {
                throw UnknownFeatureException::forKey($key);
            }
        }

        $this->enabled = $enabled;
    }

    /**
     * @return list<FeatureModule>
     */
    public function available(): array
    {
        return array_values($this->available);
    }

    /**
     * @return list<FeatureModule>
     */
    public function enabled(): array
    {
        return array_values(array_filter(
            $this->available,
            fn (FeatureModule $module) => in_array($module->key(), $this->enabled, true),
        ));
    }

    public function isEnabled(string $key): bool
    {
        return in_array($key, $this->enabled, true);
    }

    public function find(string $key): ?FeatureModule
    {
        return $this->available[$key] ?? null;
    }
}
