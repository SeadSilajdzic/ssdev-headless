<?php

namespace Ssdev\Headless\Features\Tenancy;

use Spatie\Permission\Models\Role;

/**
 * Spatie's role, kept per tenant: each tenant and the central level define their own roles.
 */
final class TenantRole extends Role
{
    use BelongsToTenant;
}
