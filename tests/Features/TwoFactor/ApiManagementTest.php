<?php

use Workbench\App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('reports the status without the secret', function () {
    apiAs(enabledTwoFactorUser());

    getJson('/api/v1/me/two-factor')->assertOk()->assertExactJson(['data' => ['status' => 'enabled']]);
});

it('requires the password to enable, regenerate or disable', function () {
    apiAs(User::factory()->create());

    postJson('/api/v1/me/two-factor')->assertStatus(422)->assertJsonValidationErrors('password');
    postJson('/api/v1/me/two-factor', ['password' => 'wrong'])->assertStatus(422)->assertJsonValidationErrors('password');
    postJson('/api/v1/me/two-factor/recovery-codes')->assertStatus(422)->assertJsonValidationErrors('password');
    deleteJson('/api/v1/me/two-factor')->assertStatus(422)->assertJsonValidationErrors('password');
});

it('enables with a setup the client can show', function () {
    apiAs(User::factory()->create());

    postJson('/api/v1/me/two-factor', ['password' => 'password'])
        ->assertOk()
        ->assertJsonStructure(['data' => ['secret']])
        ->assertJsonPath('data.otpauth_url', fn (string $url) => str_starts_with($url, 'otpauth://totp/'))
        ->assertJsonPath('data.qr_code', fn (string $uri) => str_starts_with($uri, 'data:image/svg+xml;base64,'));

    getJson('/api/v1/me/two-factor')->assertJsonPath('data.status', 'pending');
});

it('confirms with a code and returns recovery codes', function () {
    $user = apiAs(enabledTwoFactorUser(confirmed: false));

    postJson('/api/v1/me/two-factor/confirm', ['code' => currentOtp($user)])->assertOk()->assertJsonCount(8, 'data.recovery_codes');

    getJson('/api/v1/me/two-factor')->assertJsonPath('data.status', 'enabled');
});

it('rejects a wrong confirmation code', function () {
    apiAs(enabledTwoFactorUser(confirmed: false));

    postJson('/api/v1/me/two-factor/confirm', ['code' => '000000'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code' => __('headless::two_factor.errors.invalid_code')]);
});

it('rejects enabling twice', function () {
    apiAs(enabledTwoFactorUser());

    postJson('/api/v1/me/two-factor', ['password' => 'password'])->assertStatus(422)->assertJsonValidationErrors('two_factor');
});

it('regenerates recovery codes', function () {
    $user = apiAs(enabledTwoFactorUser());
    $old = $user->recoveryCodes();

    $new = postJson('/api/v1/me/two-factor/recovery-codes', ['password' => 'password'])->assertOk()->json('data.recovery_codes');

    expect($new)->toHaveCount(8)->and(array_intersect($old, $new))->toBe([]);
});

it('disables two-factor authentication', function () {
    apiAs(enabledTwoFactorUser());

    deleteJson('/api/v1/me/two-factor', ['password' => 'password'])->assertNoContent();

    getJson('/api/v1/me/two-factor')->assertJsonPath('data.status', 'disabled');
});

it('never returns the secret once enabled', function () {
    $user = apiAs(enabledTwoFactorUser());
    $secret = decrypt($user->two_factor_secret);

    foreach (['/api/v1/me', '/api/v1/me/two-factor'] as $uri) {
        expect(getJson($uri)->assertOk()->getContent())->not->toContain($secret)->not->toContain($user->recoveryCodes()[0]);
    }
});

it('requires a token', function () {
    getJson('/api/v1/me/two-factor')->assertUnauthorized();
});
