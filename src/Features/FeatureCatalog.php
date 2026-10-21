<?php

namespace Ssdev\Headless\Features;

use Ssdev\Headless\Features\Roles\RolesModule;
use Ssdev\Headless\Features\TwoFactor\TwoFactorModule;
use Ssdev\Headless\Features\Users\UsersModule;

final class FeatureCatalog
{
    /**
     * @return list<FeatureModule>
     */
    public function modules(): array
    {
        return [new TwoFactorModule, new RolesModule, new UsersModule];
    }
}
