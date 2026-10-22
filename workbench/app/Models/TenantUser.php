<?php

namespace Workbench\App\Models;

use Ssdev\Headless\Features\Tenancy\BelongsToTenant;
use Workbench\Database\Factories\TenantUserFactory;

/**
 * The application's user model as headless:add tenancy leaves it.
 */
class TenantUser extends User
{
    use BelongsToTenant;

    protected $table = 'users';

    protected static function newFactory(): TenantUserFactory
    {
        return TenantUserFactory::new();
    }
}
