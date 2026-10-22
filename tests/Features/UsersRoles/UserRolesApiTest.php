<?php

use Ssdev\ApiContracts\Testing\InteractsWithApiContract;
use Workbench\App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

uses(InteractsWithApiContract::class);

beforeEach(fn () => config(['api-contract.snapshot_dir' => packageSnapshotDirectory(), 'api-contract.strict' => true]));

it('lists and syncs the roles of a user', function () {
    $actor = roleManager(['reports.view']);
    $actor->givePermissionTo('users.view');
    apiAs($actor);
    $user = User::factory()->create();
    $editor = makeRole('Editor', ['reports.view'], $user);
    $viewer = makeRole('Viewer', ['reports.view']);

    getJson("/api/v1/users/{$user->getKey()}/roles")->assertOk()->assertJsonPath('data', [['id' => $editor->id, 'name' => 'Editor']]);

    putJson("/api/v1/users/{$user->getKey()}/roles", ['roles' => [$viewer->id]])
        ->assertOk()
        ->assertJsonPath('data', [['id' => $viewer->id, 'name' => 'Viewer']]);
});

it('forbids changing roles without roles.manage', function () {
    apiAs(permittedUser('users.view', 'roles.view'));

    putJson('/api/v1/users/'.User::factory()->create()->getKey().'/roles', ['roles' => []])->assertForbidden();
});

it('GET /api/v1/users/{user}/roles matches contract', function () {
    apiAs(permittedUser('users.view', 'roles.view'));
    $user = User::factory()->create();
    makeRole('Editor', [], $user);

    $this->assertMatchesApiContract('GET_api_v1_users_show_roles', getJson("/api/v1/users/{$user->getKey()}/roles")->assertOk()->json());
});

it('PUT /api/v1/users/{user}/roles matches contract', function () {
    $actor = roleManager();
    $actor->givePermissionTo('users.view');
    apiAs($actor);
    $user = User::factory()->create();
    $editor = makeRole('Editor', []);

    $response = putJson("/api/v1/users/{$user->getKey()}/roles", ['roles' => [$editor->id]])->assertOk();

    $this->assertMatchesApiContract('PUT_api_v1_users_show_roles', $response->json());
});
