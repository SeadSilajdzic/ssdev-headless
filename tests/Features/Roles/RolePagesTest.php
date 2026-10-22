<?php

use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Support\Flash;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

function rolesViewer(): User
{
    $viewer = roleManager();
    $viewer->revokePermissionTo('roles.manage');

    return $viewer;
}

/**
 * @param  list<array{abilities: list<array{name: string}>}>  $groups
 * @return list<string>
 */
function pickerPermissions(array $groups): array
{
    return collect($groups)->flatMap(fn (array $group) => array_column($group['abilities'], 'name'))->all();
}

beforeEach(fn () => confirmPassword());

it('lists roles to users with roles.view', function () {
    $viewer = rolesViewer();
    makeRole('Editor', ['reports.view']);

    actingAs($viewer)->get(route('headless.roles.index'))->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Roles/Index'), false)
        ->where('roles.data.0.name', 'Editor')
        ->where('roles.data.0.permissions_count', 1)
        ->where('can.manage', false));
});

it('searches roles by name', function () {
    $viewer = rolesViewer();
    makeRole('Editor', []);
    makeRole('Support', []);

    actingAs($viewer)->get(route('headless.roles.index', ['search' => 'edi']))->assertInertia(fn (AssertableInertia $page) => $page
        ->has('roles.data', 1)
        ->where('filters.search', 'edi'));
});

it('forbids the roles pages without roles.view', function () {
    roleManager();
    $user = User::factory()->create();
    $role = makeRole('Editor', []);

    actingAs($user)->get(route('headless.roles.index'))->assertForbidden();
    actingAs($user)->get(route('headless.roles.edit', $role->getKey()))->assertForbidden();
});

it('shows the roles navigation item only with roles.view', function () {
    $viewer = rolesViewer();

    expect(navigationKeys(User::factory()->create()))->not->toContain('roles')
        ->and(navigationKeys($viewer))->toContain('roles');
});

it('creates a role', function () {
    $actor = roleManager(['reports.view']);

    $response = actingAs($actor)->post(route('headless.roles.store'), ['name' => 'Editor', 'permissions' => ['reports.view']]);

    $response->assertRedirect(route('headless.roles.edit', Role::where('name', 'Editor')->value('id')))->assertSessionHas(Flash::SUCCESS);
});

it('rejects a duplicate role name', function () {
    $actor = roleManager();
    makeRole('Editor', []);

    actingAs($actor)->post(route('headless.roles.store'), ['name' => 'Editor', 'permissions' => []])->assertSessionHasErrors('name');
});

it('updates and deletes a role', function () {
    $actor = roleManager(['reports.view']);
    $role = makeRole('Editor', ['reports.view']);

    actingAs($actor)->put(route('headless.roles.update', $role->getKey()), ['name' => 'Writer', 'permissions' => []])->assertSessionHasNoErrors();
    expect($role->fresh()->name)->toBe('Writer');

    actingAs($actor)->delete(route('headless.roles.destroy', $role->getKey()))->assertRedirect(route('headless.roles.index'));
    expect(Role::whereKey($role->getKey())->exists())->toBeFalse();
});

it('forbids the role editor with roles.manage only', function () {
    $manager = roleManager();
    $manager->revokePermissionTo('roles.view');

    actingAs($manager)->get(route('headless.roles.create'))->assertForbidden();
    actingAs($manager)->post(route('headless.roles.store'), ['name' => 'X', 'permissions' => []])->assertForbidden();

    expect(Role::where('name', 'X')->exists())->toBeFalse();
});

it('forbids changes with roles.view only', function () {
    $viewer = rolesViewer();
    $role = makeRole('Editor', []);

    actingAs($viewer)->post(route('headless.roles.store'), ['name' => 'X', 'permissions' => []])->assertForbidden();
    actingAs($viewer)->put(route('headless.roles.update', $role->getKey()), ['name' => 'X', 'permissions' => []])->assertForbidden();
    actingAs($viewer)->delete(route('headless.roles.destroy', $role->getKey()))->assertForbidden();
});

it('shows only grantable permissions in the picker', function () {
    $actor = roleManager(['reports.view']);

    actingAs($actor)->get(route('headless.roles.create'))->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Roles/Edit'), false)
        ->where('role', null)
        ->where('permissions', fn ($groups) => in_array('reports.view', pickerPermissions($groups->toArray()), true)
            && ! in_array('reports.export', pickerPermissions($groups->toArray()), true)));
});

it('answers not found for a missing role', function () {
    actingAs(roleManager())->get(route('headless.roles.edit', 999))->assertNotFound();
});

it('shows a role with permissions the manager lacks as read only', function () {
    $actor = roleManager();
    $role = makeRole('Exporters', ['reports.export']);

    actingAs($actor)->get(route('headless.roles.edit', $role->getKey()))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('can.manage', false)
        ->where('permissions', [])
        ->where('readOnlyReason', __('headless::roles.errors.role_not_manageable')));
});

it('gives viewers no read-only reason', function () {
    $role = makeRole('Editor', []);

    actingAs(rolesViewer())->get(route('headless.roles.edit', $role->getKey()))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('can.manage', false)
        ->where('readOnlyReason', null));
});

it('asks for the password before the roles pages', function () {
    session()->forget('auth.password_confirmed_at');

    actingAs(rolesViewer())->get(route('headless.roles.index'))->assertRedirect(route('password.confirm'));
});
