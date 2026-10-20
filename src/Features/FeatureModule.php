<?php

namespace Ssdev\Headless\Features;

use Illuminate\Support\ServiceProvider;

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
}
