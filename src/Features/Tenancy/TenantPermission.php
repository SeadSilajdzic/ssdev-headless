<?php

namespace Ssdev\Headless\Features\Tenancy;

use Ssdev\Headless\Permissions\HeadlessPermission;

/**
 * Permissions of the central level; TenantPermissionGate refuses them inside a tenant.
 */
enum TenantPermission: string implements HeadlessPermission
{
    case View = 'tenants.view';
    case Manage = 'tenants.manage';

    public function group(): string
    {
        return 'headless::tenancy.group';
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(fn (self $permission): string => $permission->value, self::cases());
    }
}
