<?php

use Laravel\Sanctum\PersonalAccessToken;
use Ssdev\Headless\Tests\Fixtures\Permissions\FakePermission;
use Workbench\App\Models\User;

use function Pest\Laravel\artisan;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

beforeEach(function () {
    usePermissions(FakePermission::class);
    artisan('headless:permissions:sync');
});

it('lists tokens without plain text or hashes', function () {
    $user = apiAs(User::factory()->create());
    $user->createToken('CLI');

    $response = getJson('/api/v1/tokens')->assertOk()->assertJsonPath('data.0.name', 'CLI');

    expect($response->getContent())->not->toContain(PersonalAccessToken::sole()->token)->not->toContain('|');
});

it('creates a token after checking the password', function () {
    $user = apiAs(User::factory()->create());
    $user->givePermissionTo('reports.view');

    postJson('/api/v1/tokens', ['name' => 'Zapier', 'abilities' => ['reports.view']])->assertStatus(422)->assertJsonValidationErrors('password');

    postJson('/api/v1/tokens', ['name' => 'Zapier', 'abilities' => ['reports.view'], 'password' => 'password'])
        ->assertCreated()
        ->assertJsonPath('data.token.name', 'Zapier')
        ->assertJsonPath('data.token.abilities', ['reports.view'])
        ->assertJson(fn ($json) => $json->where('data.plain_text_token', fn (string $token) => str_contains($token, '|'))->etc());
});

it('refuses abilities the owner does not have', function () {
    apiAs(User::factory()->create());

    postJson('/api/v1/tokens', ['name' => 'Zapier', 'abilities' => ['reports.view'], 'password' => 'password'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('abilities.0');
});

it('cannot give a new token more than the current token has', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('reports.view');

    withToken($user->createToken('limited', ['reports.export'])->plainTextToken)->postJson('/api/v1/tokens', ['name' => 'Escalate', 'abilities' => ['reports.view'], 'password' => 'password'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('abilities.0');
});

it('revokes an own token', function () {
    $user = apiAs(User::factory()->create());
    $token = $user->createToken('CLI')->accessToken;

    deleteJson("/api/v1/tokens/{$token->id}")->assertNoContent();

    expect(PersonalAccessToken::find($token->id))->toBeNull();
});

it('cannot revoke another user token', function () {
    $token = User::factory()->create()->createToken('theirs')->accessToken;
    apiAs(User::factory()->create());

    deleteJson("/api/v1/tokens/{$token->id}")->assertNotFound();

    expect(PersonalAccessToken::find($token->id))->not->toBeNull();
});
