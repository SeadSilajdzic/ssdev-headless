<?php

use Illuminate\Support\Facades\Notification;
use Ssdev\ApiContracts\Testing\InteractsWithApiContract;
use Workbench\App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(InteractsWithApiContract::class);

beforeEach(function () {
    config(['api-contract.snapshot_dir' => packageSnapshotDirectory(), 'api-contract.strict' => true]);
    Notification::fake();
    apiAs(usersAdmin());
});

it('GET /api/v1/users matches contract', function () {
    $this->assertMatchesApiContract('GET_api_v1_users', getJson('/api/v1/users')->assertOk()->json());
});

it('POST /api/v1/users matches contract', function () {
    $response = postJson('/api/v1/users', ['name' => 'Ada Lovelace', 'email' => 'ada@example.com'])->assertCreated();

    $this->assertMatchesApiContract('POST_api_v1_users', $response->json());
});

it('GET /api/v1/users/{user} matches contract', function () {
    $user = User::factory()->create();

    $this->assertMatchesApiContract('GET_api_v1_users_show', getJson("/api/v1/users/{$user->getKey()}")->assertOk()->json());
});

it('PUT /api/v1/users/{user} matches contract', function () {
    $user = User::factory()->create();

    $response = putJson("/api/v1/users/{$user->getKey()}", ['name' => 'Ada Byron', 'email' => $user->email])->assertOk();

    $this->assertMatchesApiContract('PUT_api_v1_users_show', $response->json());
});

it('POST /api/v1/users/{user}/block matches contract', function () {
    $user = User::factory()->create();

    $this->assertMatchesApiContract('POST_api_v1_users_show_block', postJson("/api/v1/users/{$user->getKey()}/block")->assertOk()->json());
});

it('DELETE /api/v1/users/{user}/block matches contract', function () {
    $user = User::factory()->create(['blocked_at' => now()]);

    $this->assertMatchesApiContract('DELETE_api_v1_users_show_block', deleteJson("/api/v1/users/{$user->getKey()}/block")->assertOk()->json());
});

it('DELETE /api/v1/users/{user} answers without content', function () {
    deleteJson('/api/v1/users/'.User::factory()->create()->getKey())->assertNoContent();
});
