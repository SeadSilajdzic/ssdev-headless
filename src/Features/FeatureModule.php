<?php

namespace Ssdev\Headless\Features;

use Illuminate\Support\ServiceProvider;
use Ssdev\Headless\Installation\InstallStep;

interface FeatureModule
{
    public function key(): string;

    public function label(): string;

    /**
     * @return array<string, string> package name => version constraint
     */
    public function composerPackages(): array;

    /**
     * @return class-string<ServiceProvider>
     */
    public function serviceProvider(): string;

    /**
     * @return list<class-string<InstallStep>>
     */
    public function installSteps(): array;
}
