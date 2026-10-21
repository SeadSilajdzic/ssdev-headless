<?php

use Illuminate\Support\Facades\Process;
use Spatie\Permission\Models\Permission;
use Ssdev\Headless\Tests\Fixtures\Permissions\FakePermission;

use function Pest\Laravel\artisan;

// The API contract step starts artisan processes in the project; the fixture has no artisan.
beforeEach(fn () => Process::fake());

it('adds two-factor authentication to an installed project', function () {
    $files = fixtureProject();

    artisan('headless:add', ['feature' => 'two-factor', '--no-interaction' => true])->assertSuccessful();

    expect($files->get('config/headless.php'))->toContain("'two-factor',")
        ->and($files->glob('database/migrations/*_add_two_factor_columns_to_users_table.php'))->toHaveCount(1)
        ->and($files->get('app/Models/User.php'))->toContain('TwoFactorAuthenticatable')
        ->and($files->exists('tests/Feature/HeadlessTwoFactorApiContractTest.php'))->toBeTrue();
});

it('rejects an unknown feature', function () {
    fixtureProject();

    artisan('headless:add', ['feature' => 'nope', '--no-interaction' => true])
        ->expectsOutputToContain('Unknown feature [nope]. Available features: two-factor.')
        ->assertFailed();
});

it('runs again without duplicating anything', function () {
    $files = fixtureProject();

    artisan('headless:add', ['feature' => 'two-factor', '--no-interaction' => true])->assertSuccessful();
    artisan('headless:add', ['feature' => 'two-factor', '--no-interaction' => true])->assertSuccessful();

    expect(substr_count($files->get('config/headless.php'), "'two-factor'"))->toBe(1)
        ->and($files->glob('database/migrations/*_add_two_factor_columns_to_users_table.php'))->toHaveCount(1)
        ->and(substr_count($files->get('app/Models/User.php'), 'use Laravel\Fortify\TwoFactorAuthenticatable;'))->toBe(1);
});

it('runs the migrations and syncs permissions when asked', function () {
    fixtureProject();
    usePermissions(FakePermission::class);

    artisan('headless:add', ['feature' => 'two-factor', '--migrate' => true, '--no-interaction' => true])->assertSuccessful();

    expect(Permission::count())->toBe(2);
});
