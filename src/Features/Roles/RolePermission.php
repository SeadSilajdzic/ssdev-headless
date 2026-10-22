<?php

namespace Ssdev\Headless\Features\Roles;

use Ssdev\Headless\Permissions\GlobalPermission;

enum RolePermission: string implements GlobalPermission
{
    case View = 'roles.view';
    case Manage = 'roles.manage';

    public function group(): string
    {
        return 'headless::roles.group';
    }
}
