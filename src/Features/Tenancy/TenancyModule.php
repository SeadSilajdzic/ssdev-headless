<?php

namespace Ssdev\Headless\Features\Tenancy;

use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Features\FeatureModule;

final class TenancyModule implements FeatureModule
{
    public const TENANTS_MIGRATION = 'create_tenants_table';

    public const USERS_MIGRATION = 'add_tenant_to_users_table';

    public function key(): string
    {
        return Feature::Tenancy->value;
    }

    public function label(): string
    {
        return 'Multi-tenancy';
    }

    public function composerPackages(): array
    {
        return ['spatie/laravel-multitenancy' => '^4.2'];
    }

    public function serviceProvider(): string
    {
        return TenancyServiceProvider::class;
    }

    public function installSteps(): array
    {
        return [];
    }

    public function migrations(): array
    {
        $directory = __DIR__.'/../../../database/migrations/tenancy/';

        return [
            self::TENANTS_MIGRATION => $directory.self::TENANTS_MIGRATION.'.php',
            self::USERS_MIGRATION => $directory.self::USERS_MIGRATION.'.php',
        ];
    }

    public function userTraits(): array
    {
        return [];
    }

    public function permissions(): array
    {
        return [];
    }

    public function apiContractTest(): ?string
    {
        return null;
    }
}
