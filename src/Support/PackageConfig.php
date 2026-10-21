<?php

namespace Ssdev\Headless\Support;

final class PackageConfig
{
    /**
     * Laravel merges package config one level deep, so a setting a later release adds to a group (rate_limits.api)
     * would be missing from a config the application published earlier. Groups merge one level further; lists stay as published.
     *
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $published
     * @return array<string, mixed>
     */
    public static function merge(array $defaults, array $published): array
    {
        foreach ($defaults as $key => $default) {
            if (! array_key_exists($key, $published)) {
                $published[$key] = $default;
            } elseif (is_array($default) && ! array_is_list($default) && is_array($published[$key])) {
                $published[$key] = array_merge($default, $published[$key]);
            }
        }

        return $published;
    }
}
