<?php

namespace Ssdev\Headless\Features;

use InvalidArgumentException;

final class UnknownFeatureException extends InvalidArgumentException
{
    public static function forKey(string $key): self
    {
        return new self("Unknown feature [{$key}].");
    }
}
