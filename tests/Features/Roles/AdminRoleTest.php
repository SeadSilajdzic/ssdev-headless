<?php

use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Account\AdminAccountCreator;
use Ssdev\Headless\Features\Roles\Events\RoleAssigned;
use Ssdev\Headless\Features\Roles\RolesModule;
use Ssdev\Headless\Permissions\PermissionRegistry;
use Ssdev\Headless\Tests\Fixtures\Permissions\FakePermission;
use Workbench\App\Models\User;

use function Pest\Laravel\artisan;

it('gives the first admin every permission through the Administrator role', function () {
    usePermissions(FakePermission::class);

    $admin = app(AdminAccountCreator::class)->create('Ada Lovelace', 'ada@example.com', 'S3cure-password!');

    $role = Role::findByName(RolesModule::ADMIN_ROLE, 'web');
    expect($admin->hasRole(RolesModule::ADMIN_ROLE))->toBeTrue()
        ->and($role->permissions->pluck('name')->sort()->values()->all())->toBe(collect(app(PermissionRegistry::class)->names())->sort()->values()->all())
        ->and($admin->getDirectPermissions())->toBeEmpty()
        ->and($admin->can('roles.manage'))->toBeTrue();
});

it('reuses the Administrator role for a later admin', function () {
    app(AdminAccountCreator::class)->create('Ada Lovelace', 'ada@example.com', 'S3cure-password!');
    $second = app(AdminAccountCreator::class)->create('Grace Hopper', 'grace@example.com', 'S3cure-password!');

    expect(Role::query()->where('name', RolesModule::ADMIN_ROLE)->count())->toBe(1)
        ->and($second->hasRole(RolesModule::ADMIN_ROLE))->toBeTrue();
});

it('assigns a role from the console', function () {
    Event::fake([RoleAssigned::class]);
    usePermissions(FakePermission::class);
    roleManager();
    $role = makeRole('Editor', ['reports.view']);
    $user = User::factory()->create(['email' => 'ada@example.com']);

    artisan('headless:roles:assign', ['email' => 'Ada@Example.com', 'role' => 'Editor'])->assertSuccessful();

    expect($user->fresh()->hasRole('Editor'))->toBeTrue();
    Event::assertDispatched(RoleAssigned::class, fn (RoleAssigned $event) => $event->role->is($role) && $event->actor === null);
});

it('fails for an unknown role or email', function () {
    roleManager();
    makeRole('Editor', ['reports.view']);
    User::factory()->create(['email' => 'ada@example.com']);

    artisan('headless:roles:assign', ['email' => 'ghost@example.com', 'role' => 'Editor'])->assertFailed();
    artisan('headless:roles:assign', ['email' => 'ada@example.com', 'role' => 'Ghost'])->assertFailed();
});
