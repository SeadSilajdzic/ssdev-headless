<?php

namespace Ssdev\Headless\Support;

final class SharedIsolation implements Isolation
{
    public function path(string $path): string
    {
        return $path;
    }

    public function key(string $key): string
    {
        return $key;
    }
}
