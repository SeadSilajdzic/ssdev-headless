<?php

namespace Ssdev\Headless\Features;

use Ssdev\Headless\Features\Roles\RolesModule;
use Ssdev\Headless\Features\Teams\TeamsModule;
use Ssdev\Headless\Features\Tenancy\TenancyModule;
use Ssdev\Headless\Features\TwoFactor\TwoFactorModule;
use Ssdev\Headless\Features\Users\UsersModule;

final class FeatureCatalog
{
    /**
     * @return list<FeatureModule>
     */
    public function modules(): array
    {
        return [new TwoFactorModule, new RolesModule, new UsersModule, new TeamsModule, new TenancyModule];
    }
}
