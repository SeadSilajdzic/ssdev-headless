<?php

namespace Ssdev\Headless\Features\Tenancy;

use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Carbon;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

/**
 * @property int $id
 * @property string $name
 * @property string $subdomain
 * @property Carbon|null $suspended_at
 */
final class Tenant extends SpatieTenant
{
    protected $table = 'tenants';

    protected $casts = ['suspended_at' => 'datetime'];

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function execute(callable $callable): mixed
    {
        return parent::execute(function (self $tenant) use ($callable): mixed {
            $result = $callable($tenant);

            // A pending dispatch is sent when it is destroyed, so it must not outlive this tenant: fn () => Job::dispatch().
            return $result instanceof PendingDispatch ? null : $result;
        });
    }
}
