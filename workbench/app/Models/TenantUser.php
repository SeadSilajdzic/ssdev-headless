<?php

namespace Workbench\App\Models;

use Workbench\Database\Factories\TenantUserFactory;

/**
 * The application's user model as headless:add tenancy leaves it.
 */
class TenantUser extends User
{
    protected $table = 'users';

    protected static function newFactory(): TenantUserFactory
    {
        return TenantUserFactory::new();
    }
}
