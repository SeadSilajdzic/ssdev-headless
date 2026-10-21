<?php

namespace Ssdev\Headless\Tests\Fixtures\Features;

use Ssdev\Headless\Features\FeatureModule;
use Ssdev\Headless\Installation\InstallStep;

final class FakeModule implements FeatureModule
{
    /**
     * @param  array<string, string>  $packages
     * @param  list<class-string<InstallStep>>  $installSteps
     */
    public function __construct(
        private string $key = 'fake',
        private array $packages = [],
        private array $installSteps = [],
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return 'Fake feature';
    }

    public function composerPackages(): array
    {
        return $this->packages;
    }

    public function serviceProvider(): string
    {
        return FakeFeatureServiceProvider::class;
    }

    public function installSteps(): array
    {
        return $this->installSteps;
    }

    public function migrations(): array
    {
        return [];
    }

    public function userTraits(): array
    {
        return [];
    }

    public function apiContractTest(): ?string
    {
        return null;
    }
}
