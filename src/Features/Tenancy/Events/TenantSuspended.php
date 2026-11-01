<?php

namespace Ssdev\Headless\Features\Tenancy\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Auth\User;
use Ssdev\Headless\Features\Tenancy\Tenant;

final class TenantSuspended implements ShouldDispatchAfterCommit
{
    public function __construct(
        public readonly Tenant $tenant,
        public readonly User $actor,
    ) {}
}
