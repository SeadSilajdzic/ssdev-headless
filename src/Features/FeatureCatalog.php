<?php

namespace Ssdev\Headless\Features;

use Ssdev\Headless\Features\TwoFactor\TwoFactorModule;

final class FeatureCatalog
{
    /**
     * @return list<FeatureModule>
     */
    public function modules(): array
    {
        return [new TwoFactorModule];
    }
}
