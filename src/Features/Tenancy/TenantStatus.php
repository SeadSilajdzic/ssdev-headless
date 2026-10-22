<?php

namespace Ssdev\Headless\Features\Tenancy;

enum TenantStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    public static function of(Tenant $tenant): self
    {
        return $tenant->isSuspended() ? self::Suspended : self::Active;
    }
}
