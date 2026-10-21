<?php

namespace Ssdev\Headless\Features\Roles\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Auth\User;
use Spatie\Permission\Models\Role;

// Inside a transaction it waits for the commit, so a change that is rolled back is never announced.
final class RoleAssigned implements ShouldDispatchAfterCommit
{
    public function __construct(
        public readonly Role $role,
        public readonly User $user,
        public readonly ?User $actor,
    ) {}
}
