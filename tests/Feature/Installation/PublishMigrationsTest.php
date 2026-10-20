<?php

use Composer\InstalledVersions;
use Illuminate\Support\Carbon;
use Ssdev\Headless\Installation\Steps\PublishMigrations;
use Ssdev\Headless\Installation\StepStatus;

it('publishes the permission and package migrations with timestamps', function () {
    Carbon::setTestNow('2026-10-20 12:00:00');
    $files = fixtureProject();

    $result = app(PublishMigrations::class)->handle(installContext($files));

    expect($result->status)->toBe(StepStatus::Changed)
        ->and($files->get('database/migrations/2026_10_20_120000_create_permission_tables.php'))
        ->toBe(file_get_contents(InstalledVersions::getInstallPath('spatie/laravel-permission').'/database/migrations/create_permission_tables.php.stub'))
        ->and($files->get('database/migrations/2026_10_20_120001_add_avatar_path_to_users_table.php'))
        ->toBe(file_get_contents(__DIR__.'/../../../database/migrations/add_avatar_path_to_users_table.php'));
});

it('skips migrations that are already published', function () {
    $files = fixtureProject();
    $files->put('database/migrations/2025_01_01_000000_create_permission_tables.php', '<?php');

    app(PublishMigrations::class)->handle(installContext($files));

    expect($files->glob('database/migrations/*_create_permission_tables.php'))->toBe(['database/migrations/2025_01_01_000000_create_permission_tables.php'])
        ->and($files->glob('database/migrations/*_add_avatar_path_to_users_table.php'))->toHaveCount(1)
        ->and(app(PublishMigrations::class)->handle(installContext($files))->status)->toBe(StepStatus::Skipped);
});
