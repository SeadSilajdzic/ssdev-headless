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

    /**
     * @return array<string, string> migration name without timestamp => source path
     */
    public function migrations(): array;

    /**
     * @return array<string, string> trait name => import statement the User model needs
     */
    public function userTraits(): array;

    /**
     * File name of the application's contract test, e.g. HeadlessTwoFactorApiContractTest.php; its stub lives in stubs/install.
     */
    public function apiContractTest(): ?string;
}
