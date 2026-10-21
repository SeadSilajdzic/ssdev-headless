<?php

use Ssdev\ApiContracts\Testing\InteractsWithApiContract;
use Workbench\App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(InteractsWithApiContract::class);

beforeEach(function () {
    config(['api-contract.snapshot_dir' => packageSnapshotDirectory(), 'api-contract.strict' => true]);
    apiAs(roleManager(['reports.view']));
});

it('GET /api/v1/permissions matches contract', function () {
    $this->assertMatchesApiContract('GET_api_v1_permissions', getJson('/api/v1/permissions')->assertOk()->json());
});

it('GET /api/v1/roles matches contract', function () {
    makeRole('Viewer', ['reports.view']);

    $this->assertMatchesApiContract('GET_api_v1_roles', getJson('/api/v1/roles')->assertOk()->json());
});

it('POST /api/v1/roles matches contract', function () {
    $response = postJson('/api/v1/roles', ['name' => 'Viewer', 'permissions' => ['reports.view']])->assertCreated();

    $this->assertMatchesApiContract('POST_api_v1_roles', $response->json());
});

it('GET /api/v1/roles/{role} matches contract', function () {
    $role = makeRole('Viewer', ['reports.view']);

    $this->assertMatchesApiContract('GET_api_v1_roles_show', getJson("/api/v1/roles/{$role->getKey()}")->assertOk()->json());
});

it('PUT /api/v1/roles/{role} matches contract', function () {
    $role = makeRole('Viewer', ['reports.view']);

    $response = putJson("/api/v1/roles/{$role->getKey()}", ['name' => 'Reporter', 'permissions' => ['reports.view']])->assertOk();

    $this->assertMatchesApiContract('PUT_api_v1_roles_show', $response->json());
});

it('DELETE /api/v1/roles/{role} answers without content', function () {
    deleteJson('/api/v1/roles/'.makeRole('Viewer', ['reports.view'])->getKey())->assertNoContent();
});

it('GET /api/v1/roles/{role}/members matches contract', function () {
    $role = makeRole('Viewer', ['reports.view'], User::factory()->create());

    $this->assertMatchesApiContract('GET_api_v1_roles_show_members', getJson("/api/v1/roles/{$role->getKey()}/members")->assertOk()->json());
});

it('POST /api/v1/roles/{role}/members matches contract', function () {
    $role = makeRole('Viewer', ['reports.view']);

    $response = postJson("/api/v1/roles/{$role->getKey()}/members", ['email' => User::factory()->create()->email])->assertCreated();

    $this->assertMatchesApiContract('POST_api_v1_roles_show_members', $response->json());
});

it('DELETE /api/v1/roles/{role}/members/{user} answers without content', function () {
    $member = User::factory()->create();
    $role = makeRole('Viewer', ['reports.view'], $member);

    deleteJson("/api/v1/roles/{$role->getKey()}/members/{$member->getKey()}")->assertNoContent();
});
