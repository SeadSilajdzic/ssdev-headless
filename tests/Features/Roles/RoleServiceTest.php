<?php

use Spatie\Permission\Models\Role;
use Ssdev\Headless\Features\Roles\RoleAssignmentService;
use Ssdev\Headless\Features\Roles\RoleData;
use Ssdev\Headless\Features\Roles\RoleService;
use Workbench\App\Models\User;

it('creates a role from permissions the actor holds', function () {
    $actor = roleManager(['reports.view']);

    $role = app(RoleService::class)->create($actor, new RoleData('Editor', ['reports.view']));

    expect($role->name)->toBe('Editor')
        ->and($role->permissions->pluck('name')->all())->toBe(['reports.view']);
});

it('refuses permissions the actor does not have', function () {
    $actor = roleManager(['reports.view']);

    expect(fn () => app(RoleService::class)->create($actor, new RoleData('Exporter', ['reports.export'])))->toThrow(failsOn('permissions'));
});

it('refuses unknown permissions', function () {
    $actor = roleManager();

    expect(fn () => app(RoleService::class)->create($actor, new RoleData('Ghost', ['nope.view'])))->toThrow(failsOn('permissions'));
});

it('refuses to change a role with permissions the actor lacks', function () {
    $actor = roleManager(['reports.view']);
    $role = makeRole('Exporter', ['reports.view', 'reports.export']);

    expect(fn () => app(RoleService::class)->update($actor, $role, new RoleData('Viewer', ['reports.view'])))->toThrow(failsOn('role'))
        ->and(fn () => app(RoleService::class)->delete($actor, $role))->toThrow(failsOn('role'));
});

it('keeps the last role that manages roles', function () {
    $actor = roleManager();
    $admin = makeRole('Administrator', ['roles.view', 'roles.manage'], User::factory()->create());

    expect(fn () => app(RoleService::class)->delete($actor, $admin))->toThrow(failsOn('role'))
        ->and(fn () => app(RoleService::class)->update($actor, $admin, new RoleData('Administrator', ['roles.view'])))->toThrow(failsOn('role'));
});

it('lets a managing role go once another one has a member', function () {
    $actor = roleManager();
    $admin = makeRole('Administrator', ['roles.manage'], User::factory()->create());
    makeRole('Owners', ['roles.manage'], User::factory()->create());

    app(RoleService::class)->delete($actor, $admin);

    expect(Role::where('name', 'Administrator')->exists())->toBeFalse();
});

it('updates the name and permissions of a role', function () {
    $actor = roleManager(['reports.view', 'reports.export']);
    $role = makeRole('Viewer', ['reports.view']);

    app(RoleService::class)->update($actor, $role, new RoleData('Reporter', ['reports.view', 'reports.export']));

    expect($role->fresh()->name)->toBe('Reporter')
        ->and($role->fresh()->permissions->pluck('name')->sort()->values()->all())->toBe(['reports.export', 'reports.view']);
});

it('turns a name taken by a parallel request into a validation error', function () {
    $actor = roleManager(['reports.view']);
    makeRole('Editor', []);
    $other = makeRole('Viewer', []);

    expect(fn () => app(RoleService::class)->create($actor, new RoleData('Editor', [])))->toThrow(failsOn('name'))
        ->and(fn () => app(RoleService::class)->update($actor, $other, new RoleData('Editor', [])))->toThrow(failsOn('name'));
});

it('does not count deleted members as role managers', function () {
    $actor = roleManager();
    $member = User::factory()->create();
    $admin = makeRole('Administrator', ['roles.manage'], $member);
    $gone = User::factory()->create();
    $gone->assignRole($admin);
    $gone->delete();

    expect(fn () => app(RoleAssignmentService::class)->revoke($actor, $admin, $member))->toThrow(failsOn('role'));
});
