<?php

namespace Ssdev\Headless\Features\Roles;

use Illuminate\Support\ServiceProvider;
use Ssdev\Headless\Navigation\NavigationGroup;
use Ssdev\Headless\Navigation\NavigationItem;
use Ssdev\Headless\Navigation\NavigationRegistry;
use Ssdev\Headless\Routing\PackageRoutes;

final class RolesServiceProvider extends ServiceProvider
{
    public function boot(NavigationRegistry $navigation): void
    {
        PackageRoutes::web(__DIR__.'/../../../routes/roles.php');

        $navigation->add(new NavigationItem(
            'roles',
            'headless::navigation.roles',
            'headless.roles.index',
            'shield-check',
            NavigationGroup::Administration,
            permission: RolePermission::View->value,
        ));
    }
}
