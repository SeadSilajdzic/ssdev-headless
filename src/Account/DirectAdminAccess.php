<?php

namespace Ssdev\Headless\Account;

use Illuminate\Foundation\Auth\User;
use Ssdev\Headless\Permissions\PermissionRegistry;

final class DirectAdminAccess implements AdminAccess
{
    public function __construct(private PermissionRegistry $permissions) {}

    public function grant(User $admin): void
    {
        if (method_exists($admin, 'givePermissionTo') && $this->permissions->names() !== []) {
            $admin->givePermissionTo($this->permissions->names());
        }
    }
}
