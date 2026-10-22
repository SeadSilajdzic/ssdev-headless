<?php

namespace Ssdev\Headless\Features\Tenancy;

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
}
