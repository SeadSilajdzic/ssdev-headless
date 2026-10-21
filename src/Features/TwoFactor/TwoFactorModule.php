<?php

namespace Ssdev\Headless\Features\TwoFactor;

use Composer\InstalledVersions;
use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Features\FeatureModule;

final class TwoFactorModule implements FeatureModule
{
    private const MIGRATION = 'add_two_factor_columns_to_users_table';

    public function key(): string
    {
        return Feature::TwoFactor->value;
    }

    public function label(): string
    {
        return 'Two-factor authentication';
    }

    public function composerPackages(): array
    {
        return [];
    }

    public function serviceProvider(): string
    {
        return TwoFactorServiceProvider::class;
    }

    public function installSteps(): array
    {
        return [];
    }

    public function migrations(): array
    {
        // Fortify's own migration, so a project that published it already is not given a second copy.
        return [
            self::MIGRATION => InstalledVersions::getInstallPath('laravel/fortify').'/database/migrations/2014_10_12_200000_'.self::MIGRATION.'.php',
        ];
    }

    public function userTraits(): array
    {
        return ['TwoFactorAuthenticatable' => 'use Laravel\Fortify\TwoFactorAuthenticatable;'];
    }

    public function apiContractTest(): string
    {
        return 'HeadlessTwoFactorApiContractTest.php';
    }
}
