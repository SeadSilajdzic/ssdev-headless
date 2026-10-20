<?php

use Spatie\Permission\Models\Permission;
use Ssdev\Headless\Tests\Fixtures\Permissions\FakePermission;

use function Pest\Laravel\artisan;

it('creates missing permissions once', function () {
    usePermissions(FakePermission::class);

    artisan('headless:permissions:sync')->expectsOutputToContain('Created 2 permissions.')->assertSuccessful();
    artisan('headless:permissions:sync')->expectsOutputToContain('Created 0 permissions.')->assertSuccessful();

    expect(Permission::pluck('name')->sort()->values()->all())->toBe(['reports.export', 'reports.view']);
});

it('keeps permissions the package does not know', function () {
    usePermissions(FakePermission::class);
    Permission::create(['name' => 'app.custom', 'guard_name' => 'web']);

    artisan('headless:permissions:sync')->assertSuccessful();

    expect(Permission::where('name', 'app.custom')->exists())->toBeTrue();
});
