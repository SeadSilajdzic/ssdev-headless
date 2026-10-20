<?php

namespace Ssdev\Headless\Http\Inertia;

use Inertia\Inertia;
use Inertia\Response;

final class HeadlessPage
{
    public const PREFIX = 'Headless/';

    /**
     * @param  array<string, mixed>  $props
     */
    public static function render(string $page, array $props = []): Response
    {
        return Inertia::render(self::PREFIX.$page, $props);
    }
}
