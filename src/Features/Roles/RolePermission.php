<?php

namespace Ssdev\Headless\Features\Roles;

use Ssdev\Headless\Permissions\HeadlessPermission;

enum RolePermission: string implements HeadlessPermission
{
    case View = 'roles.view';
    case Manage = 'roles.manage';

    public function group(): string
    {
        return 'headless::roles.group';
    }
}
