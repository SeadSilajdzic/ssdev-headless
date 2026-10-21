<?php

namespace Ssdev\Headless\Features\Roles;

final readonly class RoleData
{
    /**
     * @param  list<string>  $permissions
     */
    public function __construct(
        public string $name,
        public array $permissions,
    ) {}
}
