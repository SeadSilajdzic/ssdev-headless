<?php

namespace Ssdev\Headless\Navigation;

final class NavigationItem
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $route,
        public readonly string $icon,
        public readonly NavigationGroup $group,
        public readonly ?string $permission = null,
        public readonly int $order = 0,
    ) {}
}
