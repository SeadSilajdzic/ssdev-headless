<?php

use Inertia\Testing\AssertableInertia;
use Laravel\Sanctum\PersonalAccessToken;
use Ssdev\Headless\Tests\Fixtures\Permissions\FakePermission;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;

beforeEach(function () {
    usePermissions(FakePermission::class);
    artisan('headless:permissions:sync');
});

function confirmedSession(User $user): void
{
    actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
}

it('asks for the password before showing the tokens page', function () {
    actingAs(User::factory()->create())->get(route('headless.api-tokens.index'))->assertRedirect(route('password.confirm'));
});

it('lists the user tokens without secrets', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('reports.view');
    $user->createToken('CLI', ['reports.view']);
    confirmedSession($user);

    $response = $this->get(route('headless.api-tokens.index'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Settings/ApiTokens'), false)
        ->where('tokens.0.name', 'CLI')
        ->where('tokens.0.abilities', ['reports.view'])
        ->where('permissions.0.abilities.0.name', 'reports.view')
        ->where('createdToken', null)
        ->where('headless.navigation.1.items.2.key', 'api-tokens'));

    expectNoSensitiveProps($response, $user);
    expect($response->getContent())->not->toContain(PersonalAccessToken::sole()->token);
});

it('creates a token with abilities the user has', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('reports.view');
    confirmedSession($user);

    $this->post(route('headless.api-tokens.store'), ['name' => 'Zapier', 'abilities' => ['reports.view']])
        ->assertRedirect(route('headless.api-tokens.index'))
        ->assertSessionHasNoErrors();

    expect($user->tokens()->sole()->abilities)->toBe(['reports.view']);
});

it('refuses abilities the user does not have', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('reports.view');
    confirmedSession($user);

    $this->post(route('headless.api-tokens.store'), ['name' => 'Zapier', 'abilities' => ['reports.export']])->assertSessionHasErrors('abilities.0');

    expect($user->tokens()->count())->toBe(0);
});

it('shows the new token only once', function () {
    $user = User::factory()->create();
    confirmedSession($user);

    $this->post(route('headless.api-tokens.store'), ['name' => 'Zapier', 'abilities' => []]);

    $this->get(route('headless.api-tokens.index'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('createdToken', fn (string $token) => str_contains($token, '|')));
    $this->get(route('headless.api-tokens.index'))->assertInertia(fn (AssertableInertia $page) => $page->where('createdToken', null));
});

it('revokes a token of the current user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('CLI')->accessToken;
    confirmedSession($user);

    $this->delete(route('headless.api-tokens.destroy', $token->id))->assertRedirect();

    expect(PersonalAccessToken::find($token->id))->toBeNull();
});

it('returns not found for another user token', function () {
    $token = User::factory()->create()->createToken('theirs')->accessToken;
    confirmedSession(User::factory()->create());

    $this->delete(route('headless.api-tokens.destroy', $token->id))->assertNotFound();

    expect(PersonalAccessToken::find($token->id))->not->toBeNull();
});
