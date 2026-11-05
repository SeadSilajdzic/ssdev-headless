<?php

use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Features\Roles\Events\RoleAssigned;
use Ssdev\Headless\Features\Roles\Events\RoleRevoked;
use Ssdev\Headless\Features\Roles\UserRoleService;
use Ssdev\Headless\Support\Flash;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

/**
 * @param  list<string>  $permissions
 */
function userRolesManager(array $permissions = []): User
{
    $actor = roleManager($permissions);
    $actor->givePermissionTo('users.view');

    return $actor;
}

beforeEach(fn () => confirmPassword());

it('shows the roles on the user page', function () {
    $actor = userRolesManager(['reports.view']);
    $user = User::factory()->create();
    $editor = makeRole('Editor', ['reports.view'], $user);
    $exporter = makeRole('Exporter', ['reports.export']);

    actingAs($actor)->get(route('headless.users.show', $user->id))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('roles', [
            ['id' => $editor->id, 'name' => 'Editor', 'assigned' => true, 'manageable' => true],
            ['id' => $exporter->id, 'name' => 'Exporter', 'assigned' => false, 'manageable' => false],
        ])
        ->where('rolesUrl', route('headless.users.roles.update', $user->id))
        ->where('can.manageRoles', true));
});

it('adds and removes roles of a user', function () {
    Event::fake([RoleAssigned::class, RoleRevoked::class]);
    $actor = userRolesManager(['reports.view', 'reports.export']);
    $user = User::factory()->create();
    $editor = makeRole('Editor', ['reports.view'], $user);
    $exporter = makeRole('Exporter', ['reports.export']);

    actingAs($actor)->put(route('headless.users.roles.update', $user->id), ['roles' => [$exporter->id]])->assertSessionHas(Flash::SUCCESS);

    expect($user->fresh()->hasRole('Exporter'))->toBeTrue()
        ->and($user->fresh()->hasRole('Editor'))->toBeFalse();
    Event::assertDispatched(RoleAssigned::class, fn (RoleAssigned $event) => $event->role->is($exporter));
    Event::assertDispatched(RoleRevoked::class, fn (RoleRevoked $event) => $event->role->is($editor));
});

it('changes nothing when one of the roles cannot be managed', function () {
    $actor = userRolesManager(['reports.view']);
    $user = User::factory()->create();
    $viewer = makeRole('Viewer', ['reports.view']);
    $exporter = makeRole('Exporter', ['reports.export']);

    actingAs($actor)->put(route('headless.users.roles.update', $user->id), ['roles' => [$viewer->id, $exporter->id]])->assertSessionHasErrors('role');

    expect($user->fresh()->hasRole('Viewer'))->toBeFalse();
});

it('refuses to save over roles that changed after the page opened', function () {
    $actor = userRolesManager(['reports.view']);
    $user = User::factory()->create();
    makeRole('Editor', ['reports.view'], $user);
    $viewer = makeRole('Viewer', ['reports.view']);

    actingAs($actor)->put(route('headless.users.roles.update', $user->id), ['roles' => [$viewer->id], 'known_roles' => []])
        ->assertSessionHasErrors(['role' => __('headless::roles.errors.roles_changed')]);

    expect($user->fresh()->hasRole('Editor'))->toBeTrue()
        ->and($user->fresh()->hasRole('Viewer'))->toBeFalse();
});

it('saves when the roles are still as the page showed them', function () {
    $actor = userRolesManager(['reports.view']);
    $user = User::factory()->create();
    $editor = makeRole('Editor', ['reports.view'], $user);
    $viewer = makeRole('Viewer', ['reports.view']);

    actingAs($actor)->put(route('headless.users.roles.update', $user->id), ['roles' => [$viewer->id], 'known_roles' => [$editor->id]])
        ->assertSessionHas(Flash::SUCCESS);

    expect($user->fresh()->hasRole('Viewer'))->toBeTrue()
        ->and($user->fresh()->hasRole('Editor'))->toBeFalse();
});

it('keeps the last member of the last managing role', function () {
    $actor = userRolesManager();
    $user = User::factory()->create();
    makeRole('Managers', ['roles.manage'], $user);

    actingAs($actor)->put(route('headless.users.roles.update', $user->id), ['roles' => []])->assertSessionHasErrors('role');

    expect($user->fresh()->hasRole('Managers'))->toBeTrue();
});

it('announces role changes only once they are saved', function () {
    $actor = roleManager(['reports.view']);
    $user = User::factory()->create();
    makeRole('Managers', ['roles.manage'], $user);
    $viewer = makeRole('Viewer', ['reports.view']);
    $announced = [];
    Event::listen(RoleAssigned::class, function (RoleAssigned $event) use (&$announced) {
        $announced[] = $event->role->name;
    });

    expect(fn () => app(UserRoleService::class)->sync($actor, $user, [$viewer->id]))->toThrow(failsOn('role'))
        ->and($announced)->toBe([])
        ->and($user->fresh()->hasRole('Viewer'))->toBeFalse();
});

it('rejects a role that does not exist', function () {
    $actor = userRolesManager();

    actingAs($actor)->put(route('headless.users.roles.update', User::factory()->create()->id), ['roles' => [999]])->assertSessionHasErrors('roles');
});

it('shows the roles read only without roles.manage', function () {
    $actor = permittedUser('users.view', 'roles.view');
    $user = User::factory()->create();
    makeRole('Editor', [], $user);

    actingAs($actor)->get(route('headless.users.show', $user->id))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('roles.0.manageable', false)
        ->where('can.manageRoles', false));
    actingAs($actor)->put(route('headless.users.roles.update', $user->id), ['roles' => []])->assertForbidden();
});
