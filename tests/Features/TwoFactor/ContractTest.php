<?php

use Ssdev\ApiContracts\Testing\InteractsWithApiContract;

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
