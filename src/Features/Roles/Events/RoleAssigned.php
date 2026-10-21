<?php

namespace Ssdev\Headless\Features\Roles\Events;

use Illuminate\Foundation\Auth\User;
use Spatie\Permission\Models\Role;

final class RoleAssigned
{
    public function __construct(
        public readonly Role $role,
        public readonly User $user,
        public readonly ?User $actor,
    ) {}
}
