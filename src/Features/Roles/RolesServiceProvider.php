<?php

namespace Ssdev\Headless\Features\Roles;

use Illuminate\Support\ServiceProvider;
use Ssdev\Headless\Account\AdminAccess;
use Ssdev\Headless\Features\Roles\Console\AssignRoleCommand;
use Ssdev\Headless\Navigation\NavigationGroup;
use Ssdev\Headless\Navigation\NavigationItem;
use Ssdev\Headless\Navigation\NavigationRegistry;
use Ssdev\Headless\Routing\PackageRoutes;

final class RolesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AdminAccess::class, AdministratorRoleAccess::class);
    }

    public function boot(NavigationRegistry $navigation): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([AssignRoleCommand::class]);
        }

        PackageRoutes::web(__DIR__.'/../../../routes/roles.php');
        PackageRoutes::api(__DIR__.'/../../../routes/roles-api.php');

        $navigation->add(new NavigationItem(
            'roles',
            'headless::navigation.roles',
            'headless.roles.index',
            'shield-check',
            NavigationGroup::Administration,
            permission: RolePermission::View->value,
            order: 10,
        ));
    }
}
