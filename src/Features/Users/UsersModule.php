<?php

namespace Ssdev\Headless\Features\Users;

use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Features\FeatureModule;

final class UsersModule implements FeatureModule
{
    public const MIGRATION = 'add_user_management_columns_to_users_table';

    public const INVITATION_TOKENS_MIGRATION = 'create_user_invitation_tokens_table';

    public const INVITATION_TOKENS_TABLE = 'user_invitation_tokens';

    public function key(): string
    {
        return Feature::Users->value;
    }

    public function label(): string
    {
        return 'User management';
    }

    public function composerPackages(): array
    {
        return [];
    }

    public function serviceProvider(): string
    {
        return UsersServiceProvider::class;
    }

    public function installSteps(): array
    {
        return [];
    }

    public function migrations(): array
    {
        return [
            self::MIGRATION => __DIR__.'/../../../database/migrations/users/'.self::MIGRATION.'.php',
            self::INVITATION_TOKENS_MIGRATION => __DIR__.'/../../../database/migrations/users/'.self::INVITATION_TOKENS_MIGRATION.'.php',
        ];
    }

    public function userTraits(): array
    {
        return ['SoftDeletes' => 'use Illuminate\Database\Eloquent\SoftDeletes;'];
    }

    public function permissions(): array
    {
        return [UserPermission::class];
    }

    public function apiContractTest(): string
    {
        return 'HeadlessUsersApiContractTest.php';
    }
}
