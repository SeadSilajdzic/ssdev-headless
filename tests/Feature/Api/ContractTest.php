<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Ssdev\ApiContracts\Testing\InteractsWithApiContract;
use Ssdev\Headless\Tests\Fixtures\Permissions\FakePermission;
use Workbench\App\Models\User;

use function Pest\Laravel\artisan;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(InteractsWithApiContract::class);

function contractUser(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo('reports.view');

    return $user;
}

beforeEach(function () {
    config(['api-contract.snapshot_dir' => packageSnapshotDirectory(), 'api-contract.strict' => true]);
    Notification::fake();
    Storage::fake('public');
    usePermissions(FakePermission::class);
    artisan('headless:permissions:sync');
});

it('POST /api/v1/auth/token matches contract', function () {
    $user = contractUser();

    $response = postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'password', 'device_name' => 'cli'])->assertCreated();

    $this->assertMatchesApiContract('POST_api_v1_auth_token', $response->json());
});

it('POST /api/v1/auth/token matches contract when credentials are wrong', function () {
    $response = postJson('/api/v1/auth/token', ['email' => 'ghost@example.com', 'password' => 'nope', 'device_name' => 'cli'])->assertStatus(422);

    $this->assertMatchesApiContract('POST_api_v1_auth_token__422', $response->json());
});

it('DELETE /api/v1/auth/token answers without content', function () {
    apiAs(contractUser());

    deleteJson('/api/v1/auth/token')->assertNoContent();
});

it('POST /api/v1/auth/register matches contract', function () {
    $response = postJson('/api/v1/auth/register', ['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'S3cure-password!'])->assertAccepted();

    $this->assertMatchesApiContract('POST_api_v1_auth_register', $response->json());
});

it('POST /api/v1/auth/forgot-password matches contract', function () {
    $response = postJson('/api/v1/auth/forgot-password', ['email' => 'ada@example.com'])->assertAccepted();

    $this->assertMatchesApiContract('POST_api_v1_auth_forgot-password', $response->json());
});

it('GET /api/v1/me matches contract', function () {
    apiAs(contractUser());

    $this->assertMatchesApiContract('GET_api_v1_me', getJson('/api/v1/me')->assertOk()->json());
});

it('GET /api/v1/me matches contract without a token', function () {
    $this->assertMatchesApiContract('GET_api_v1_me__401', getJson('/api/v1/me')->assertUnauthorized()->json());
});

it('PUT /api/v1/me matches contract', function () {
    $user = apiAs(contractUser());

    $response = putJson('/api/v1/me', ['name' => 'Grace', 'email' => $user->email])->assertOk();

    $this->assertMatchesApiContract('PUT_api_v1_me', $response->json());
});

it('PUT /api/v1/me/password answers without content', function () {
    apiAs(contractUser());

    putJson('/api/v1/me/password', ['current_password' => 'password', 'password' => 'N3w-password!', 'password_confirmation' => 'N3w-password!'])->assertNoContent();
});

it('POST /api/v1/me/avatar matches contract', function () {
    apiAs(contractUser());

    $response = postJson('/api/v1/me/avatar', ['avatar' => UploadedFile::fake()->image('me.png', 100, 100)])->assertOk();

    $this->assertMatchesApiContract('POST_api_v1_me_avatar', $response->json());
});

it('DELETE /api/v1/me/avatar matches contract', function () {
    apiAs(contractUser());

    $this->assertMatchesApiContract('DELETE_api_v1_me_avatar', deleteJson('/api/v1/me/avatar')->assertOk()->json());
});

it('POST /api/v1/me/email/verification-notification matches contract', function () {
    $user = User::factory()->unverified()->create();
    apiAs($user);

    $response = postJson('/api/v1/me/email/verification-notification')->assertAccepted();

    $this->assertMatchesApiContract('POST_api_v1_me_email_verification-notification', $response->json());
});

it('DELETE /api/v1/me answers without content', function () {
    apiAs(contractUser());

    deleteJson('/api/v1/me', ['password' => 'password'])->assertNoContent();
});

it('GET /api/v1/tokens matches contract', function () {
    $user = apiAs(contractUser());
    $user->createToken('CLI', ['reports.view']);

    $this->assertMatchesApiContract('GET_api_v1_tokens', getJson('/api/v1/tokens')->assertOk()->json());
});

it('POST /api/v1/tokens matches contract', function () {
    apiAs(contractUser());

    $response = postJson('/api/v1/tokens', ['name' => 'Zapier', 'abilities' => ['reports.view'], 'password' => 'password'])->assertCreated();

    $this->assertMatchesApiContract('POST_api_v1_tokens', $response->json());
});

it('DELETE /api/v1/tokens/{token} answers without content', function () {
    $user = apiAs(contractUser());
    $token = $user->createToken('CLI')->accessToken;

    deleteJson("/api/v1/tokens/{$token->id}")->assertNoContent();
});

it('unknown api routes match the not found contract', function () {
    $this->assertMatchesApiContract('ANY_api_v1_unknown__404', getJson('/api/v1/nope')->assertNotFound()->json());
});
