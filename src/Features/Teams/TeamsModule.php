<?php

namespace Ssdev\Headless\Features\Teams;

use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Features\FeatureModule;

final class TeamsModule implements FeatureModule
{
    public const TEAMS_MIGRATION = 'create_teams_tables';

    public const PERMISSIONS_MIGRATION = 'add_teams_to_permission_tables';

    public function key(): string
    {
        return Feature::Teams->value;
    }

    public function label(): string
    {
        return 'Teams';
    }

    public function composerPackages(): array
    {
        return [];
    }

    public function serviceProvider(): string
    {
        return TeamsServiceProvider::class;
    }

    public function installSteps(): array
    {
        return [];
    }

    public function migrations(): array
    {
        $directory = __DIR__.'/../../../database/migrations/teams/';

        return [
            self::TEAMS_MIGRATION => $directory.self::TEAMS_MIGRATION.'.php',
            self::PERMISSIONS_MIGRATION => $directory.self::PERMISSIONS_MIGRATION.'.php',
        ];
    }

    // Memberships are read through Team and TeamRepository, so the User model needs no trait.
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
