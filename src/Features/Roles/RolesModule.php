<?php

namespace Ssdev\Headless\Features\Roles;

use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Features\FeatureModule;

final class RolesModule implements FeatureModule
{
    public function key(): string
    {
        return Feature::Roles->value;
    }

    public function label(): string
    {
        return 'Roles & permissions';
    }

    public function composerPackages(): array
    {
        return [];
    }

    public function serviceProvider(): string
    {
        return RolesServiceProvider::class;
    }

    public function installSteps(): array
    {
        return [];
    }

    // The permission tables and the HasRoles trait are part of the core.
    public function migrations(): array
    {
        return [];
    }

    public function userTraits(): array
    {
        return [];
    }

    public function permissions(): array
    {
        return [RolePermission::class];
    }

    public function apiContractTest(): ?string
    {
        return null;
    }
}
