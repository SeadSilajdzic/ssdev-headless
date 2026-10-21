<?php

use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\PersonalAccessToken;
use Workbench\App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

beforeEach(fn () => apiTestRoute('get', 'whoami', fn (Request $request) => $request->user()->email, ['auth:sanctum']));

it('issues a bearer token for valid credentials', function () {
    $user = User::factory()->create();

    $response = postJson('/api/v1/auth/token', ['email' => strtoupper($user->email), 'password' => 'password', 'device_name' => 'iPhone'])
        ->assertCreated()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.expires_at', null);

    expect($user->tokens()->sole()->name)->toBe('iPhone');
    getJson('/api/v1/whoami', ['Authorization' => 'Bearer '.$response->json('data.token')])->assertOk()->assertSee($user->email);
});

it('answers wrong passwords and unknown emails the same way', function () {
    $user = User::factory()->create();

    $wrongPassword = postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'nope', 'device_name' => 'cli'])->assertStatus(422);
    $unknownEmail = postJson('/api/v1/auth/token', ['email' => 'ghost@example.com', 'password' => 'nope', 'device_name' => 'cli'])->assertStatus(422);

    expect($wrongPassword->json())->toBe($unknownEmail->json())
        ->and($wrongPassword->json('errors.email.0'))->toBe(__('auth.failed'))
        ->and(PersonalAccessToken::count())->toBe(0);
});

it('fires the failed event without the password', function () {
    Event::fake([Failed::class]);
    $user = User::factory()->create();

    postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'secret-attempt', 'device_name' => 'cli']);

    Event::assertDispatched(Failed::class, fn (Failed $event) => $event->credentials === ['email' => $user->email]);
});

it('throttles token requests with the login limiter', function () {
    config(['headless.rate_limits.login' => 1]);
    $user = User::factory()->create();

    postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'nope', 'device_name' => 'cli']);
    postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'password', 'device_name' => 'cli'])->assertStatus(429);

    expect(PersonalAccessToken::count())->toBe(0);
});

it('sets the configured expiry on new tokens', function () {
    config(['headless.api.token_expiration' => 60]);
    $user = User::factory()->create();

    $expiresAt = postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'password', 'device_name' => 'cli'])->json('data.expires_at');

    expect(CarbonImmutable::parse($expiresAt)->diffInMinutes(now(), absolute: true))->toBeBetween(59, 61);
});

it('revokes only the current token on logout', function () {
    $user = User::factory()->create();
    $current = $user->createToken('current')->plainTextToken;
    $user->createToken('other');

    withToken($current)->deleteJson('/api/v1/auth/token')->assertNoContent();

    expect($user->tokens()->pluck('name')->all())->toBe(['other']);
});

it('validates the device name', function () {
    $user = User::factory()->create();

    postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'password'])->assertStatus(422)->assertJsonValidationErrors('device_name');
});

it('answers unknown emails no faster than wrong passwords', function () {
    $start = microtime(true);

    postJson('/api/v1/auth/token', ['email' => 'ghost@example.com', 'password' => 'nope', 'device_name' => 'cli'])->assertStatus(422);

    expect(microtime(true) - $start)->toBeGreaterThanOrEqual(0.2);
});
