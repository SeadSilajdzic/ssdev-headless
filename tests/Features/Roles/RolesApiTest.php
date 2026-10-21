<?php

use Spatie\Permission\Models\Role;
use Workbench\App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('lists roles with roles.view', function () {
    roleManager();
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('roles.view');
    makeRole('Viewer', ['reports.view']);

    apiAs($viewer);

    getJson('/api/v1/roles')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Viewer')
        ->assertJsonPath('meta.total', 1);
});

it('forbids roles without roles.view', function () {
    roleManager();
    apiAs(User::factory()->create());

    getJson('/api/v1/roles')->assertForbidden()->assertJsonStructure(['message']);
});

it('respects token abilities', function () {
    apiAs(roleManager(['reports.view']), ['roles.view']);

    postJson('/api/v1/roles', ['name' => 'Viewer', 'permissions' => ['reports.view']])->assertForbidden();
});

it('lists the permissions the actor can give', function () {
    apiAs(roleManager(['reports.view']));

    getJson('/api/v1/permissions')
        ->assertOk()
        ->assertJsonFragment(['name' => 'reports.view']);
});

it('lists the permissions for a viewer too', function () {
    roleManager();
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('roles.view');

    apiAs($viewer);

    getJson('/api/v1/permissions')->assertOk()->assertJsonFragment(['name' => 'roles.view']);
});

it('forbids the permissions without a roles permission', function () {
    roleManager();
    apiAs(User::factory()->create());

    getJson('/api/v1/permissions')->assertForbidden();
});

it('creates, updates and deletes a role', function () {
    apiAs(roleManager(['reports.view', 'reports.export']));

    $id = postJson('/api/v1/roles', ['name' => 'Viewer', 'permissions' => ['reports.view']])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Viewer')
        ->assertJsonPath('data.permissions', ['reports.view'])
        ->json('data.id');

    putJson("/api/v1/roles/{$id}", ['name' => 'Reporter', 'permissions' => ['reports.view', 'reports.export']])
        ->assertOk()
        ->assertJsonPath('data.name', 'Reporter')
        ->assertJsonPath('data.permissions', ['reports.export', 'reports.view']);

    getJson("/api/v1/roles/{$id}")->assertOk()->assertJsonPath('data.permissions_count', 2);

    deleteJson("/api/v1/roles/{$id}")->assertNoContent();

    expect(Role::query()->find($id))->toBeNull();
});

it('rejects a role name that is taken', function () {
    apiAs(roleManager(['reports.view']));
    makeRole('Viewer', ['reports.view']);

    postJson('/api/v1/roles', ['name' => 'Viewer', 'permissions' => []])->assertUnprocessable()->assertJsonValidationErrors('name');
});

it('rejects permissions the actor does not hold', function () {
    apiAs(roleManager());

    postJson('/api/v1/roles', ['name' => 'Viewer', 'permissions' => ['reports.view']])->assertUnprocessable()->assertJsonValidationErrors('permissions');
});

it('manages members', function () {
    apiAs(roleManager(['reports.view']));
    $role = makeRole('Viewer', ['reports.view']);
    $user = User::factory()->create();

    postJson("/api/v1/roles/{$role->getKey()}/members", ['email' => $user->email])
        ->assertCreated()
        ->assertJsonPath('data.email', $user->email);

    getJson("/api/v1/roles/{$role->getKey()}/members")->assertOk()->assertJsonPath('data.0.id', $user->getKey());

    deleteJson("/api/v1/roles/{$role->getKey()}/members/{$user->getKey()}")->assertNoContent();

    expect($user->fresh()->hasRole('Viewer'))->toBeFalse();
});

it('pages members with the page parameter', function () {
    apiAs(roleManager(['reports.view']));
    $role = makeRole('Viewer', ['reports.view']);
    User::factory()->count(21)->create()->each(fn (User $user) => $user->assignRole($role));

    getJson("/api/v1/roles/{$role->getKey()}/members?page=2")
        ->assertOk()
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonCount(1, 'data');
});

it('keeps the last member of the last managing role', function () {
    apiAs(roleManager());
    $member = User::factory()->create();
    $admin = makeRole('Administrator', ['roles.manage'], $member);

    deleteJson("/api/v1/roles/{$admin->getKey()}/members/{$member->getKey()}")->assertUnprocessable()->assertJsonValidationErrors('role');
});

it('answers 404 for a missing role', function () {
    apiAs(roleManager());

    getJson('/api/v1/roles/999')->assertNotFound()->assertJsonStructure(['message']);
});

it('answers 404 for a user who is not a member', function () {
    apiAs(roleManager(['reports.view']));
    $role = makeRole('Viewer', ['reports.view']);
    $stranger = User::factory()->create();

    deleteJson("/api/v1/roles/{$role->getKey()}/members/{$stranger->getKey()}")->assertNotFound();
});
