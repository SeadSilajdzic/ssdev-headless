<?php

namespace Ssdev\Headless\Features\Tenancy\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Auth\User;

final class TenantDeleted implements ShouldDispatchAfterCommit
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $subdomain,
        public readonly User $actor,
    ) {}
}
