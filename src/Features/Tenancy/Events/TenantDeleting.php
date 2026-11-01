<?php

namespace Ssdev\Headless\Features\Tenancy\Events;

use Ssdev\Headless\Features\Tenancy\Tenant;

/**
 * Dispatched inside the tenant and the deletion's transaction, so listeners delete the application's own rows with it.
 */
final class TenantDeleting
{
    public function __construct(public readonly Tenant $tenant) {}
}
