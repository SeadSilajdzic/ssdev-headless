<?php

namespace Ssdev\Headless\Features\Users;

use Ssdev\Headless\Permissions\HeadlessPermission;

enum UserPermission: string implements HeadlessPermission
{
    case View = 'users.view';
    case Create = 'users.create';
    case Update = 'users.update';
    case Block = 'users.block';
    case Delete = 'users.delete';

    public function group(): string
    {
        return 'headless::users.group';
    }
}
