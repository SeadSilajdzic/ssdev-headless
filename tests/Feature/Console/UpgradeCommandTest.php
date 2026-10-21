<?php

use Ssdev\Headless\Features\TwoFactor\TwoFactorModule;

use function Pest\Laravel\artisan;

it('publishes migrations of enabled features', function () {
    $files = fixtureProject();
    useFeatures([new TwoFactorModule], ['two-factor']);

    artisan('headless:upgrade')->expectsOutputToContain('add_two_factor_columns_to_users_table')->assertSuccessful();

    expect($files->glob('database/migrations/*_add_two_factor_columns_to_users_table.php'))->toHaveCount(1);
});

it('publishes only new migrations', function () {
    $files = fixtureProject();
    $files->put('database/migrations/2025_01_01_000000_create_permission_tables.php', '<?php');

    artisan('headless:upgrade')
        ->expectsOutputToContain('add_avatar_path_to_users_table')
        ->doesntExpectOutputToContain('create_permission_tables')
        ->assertSuccessful();

    artisan('headless:upgrade')->expectsOutputToContain('up to date')->assertSuccessful();

    expect($files->glob('database/migrations/*_add_avatar_path_to_users_table.php'))->toHaveCount(1);
});
