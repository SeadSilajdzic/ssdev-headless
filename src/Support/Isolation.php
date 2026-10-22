<?php

namespace Ssdev\Headless\Support;

/**
 * Keeps stored files and keys that are not tied to a user apart, e.g. per tenant; use it for the application's own too.
 */
interface Isolation
{
    public function path(string $path): string;

    public function key(string $key): string;
}
