<?php

use Ssdev\ApiContracts\Testing\InteractsWithApiContract;
use Workbench\App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(InteractsWithApiContract::class);

beforeEach(fn () => config(['api-contract.snapshot_dir' => packageSnapshotDirectory(), 'api-contract.strict' => true]));

it('POST /api/v1/auth/token matches contract when a challenge is needed', function () {
    $user = enabledTwoFactorUser();

    $response = postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'password', 'device_name' => 'cli'])->assertStatus(202);

    $this->assertMatchesApiContract('POST_api_v1_auth_token__202', $response->json());
});

it('POST /api/v1/auth/two-factor-challenge matches contract', function () {
    $user = enabledTwoFactorUser();
    $challenge = postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'password', 'device_name' => 'cli'])->json('data.challenge_token');

    $response = postJson('/api/v1/auth/two-factor-challenge', ['challenge_token' => $challenge, 'code' => currentOtp($user)])->assertCreated();

    $this->assertMatchesApiContract('POST_api_v1_auth_two-factor-challenge', $response->json());
});

it('GET /api/v1/me/two-factor matches contract', function () {
    apiAs(enabledTwoFactorUser());

    $this->assertMatchesApiContract('GET_api_v1_me_two-factor', getJson('/api/v1/me/two-factor')->assertOk()->json());
});

it('POST /api/v1/me/two-factor matches contract', function () {
    apiAs(User::factory()->create());

    $response = postJson('/api/v1/me/two-factor', ['password' => 'password'])->assertOk();

    $this->assertMatchesApiContract('POST_api_v1_me_two-factor', $response->json());
});

it('POST /api/v1/me/two-factor/confirm matches contract', function () {
    $user = apiAs(enabledTwoFactorUser(confirmed: false));

    $response = postJson('/api/v1/me/two-factor/confirm', ['code' => currentOtp($user)])->assertOk();

    $this->assertMatchesApiContract('POST_api_v1_me_two-factor_confirm', $response->json());
});

it('POST /api/v1/me/two-factor/recovery-codes matches contract', function () {
    apiAs(enabledTwoFactorUser());

    $response = postJson('/api/v1/me/two-factor/recovery-codes', ['password' => 'password'])->assertOk();

    $this->assertMatchesApiContract('POST_api_v1_me_two-factor_recovery-codes', $response->json());
});

it('DELETE /api/v1/me/two-factor answers without content', function () {
    apiAs(enabledTwoFactorUser());

    deleteJson('/api/v1/me/two-factor', ['password' => 'password'])->assertNoContent();
});
