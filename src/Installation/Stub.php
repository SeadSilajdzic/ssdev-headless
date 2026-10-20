<?php

namespace Ssdev\Headless\Installation;

final class Stub
{
    public static function get(string $name): string
    {
        return (string) file_get_contents(__DIR__."/../../stubs/install/{$name}.stub");
    }
}
