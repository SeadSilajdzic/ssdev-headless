<?php

namespace Ssdev\Headless\Features\Tenancy;

/**
 * Rows of the central level carry tenant 0, so one unique index covers central and tenant accounts alike.
 */
final class TenantContext
{
    public const CENTRAL = 0;

    public const COLUMN = 'tenant_id';

    public static function currentKey(): int
    {
        return (int) (Tenant::current()?->getKey() ?? self::CENTRAL);
    }
}
