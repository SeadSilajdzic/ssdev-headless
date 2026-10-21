<?php

use Ssdev\Headless\Support\Flash;
use Ssdev\Headless\Tests\Fixtures\Permissions\FakePermission;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

it('sends users who must use two-factor authentication to the setup page', function () {
    config(['headless.two_factor.required' => true]);

    actingAs(User::factory()->create())
        ->get(route('headless.dashboard'))
        ->assertRedirect(route('headless.two-factor.edit'))
        ->assertSessionHas(Flash::ERROR, __('headless::two_factor.required'));
});

it('lets them reach setup, password confirmation and logout', function () {
    config(['headless.two_factor.required' => true]);
    actingAs(User::factory()->create());

    get(route('password.confirm'))->assertOk();
    withSession(['auth.password_confirmed_at' => time()])->get(route('headless.two-factor.edit'))->assertOk();
    post(route('headless.two-factor.enable'))->assertRedirect(route('headless.two-factor.edit'));
    post(route('logout'))->assertRedirect();

    assertGuest();
});

it('requires it only for users with a listed permission', function () {
    usePermissions(FakePermission::class);
    artisan('headless:permissions:sync');
    config(['headless.two_factor.required_for_permissions' => ['reports.view']]);
    $admin = User::factory()->create();
    $admin->givePermissionTo('reports.view');

    actingAs(User::factory()->create())->get(route('headless.dashboard'))->assertOk();
    actingAs($admin)->get(route('headless.dashboard'))->assertRedirect(route('headless.two-factor.edit'));
});

it('keeps users with pending setup on the setup page', function () {
    config(['headless.two_factor.required' => true]);

    actingAs(enabledTwoFactorUser(confirmed: false))->get(route('headless.dashboard'))->assertRedirect(route('headless.two-factor.edit'));
});

it('lets users with two-factor authentication through', function () {
    config(['headless.two_factor.required' => true]);

    actingAs(enabledTwoFactorUser())->get(route('headless.dashboard'))->assertOk();
});

it('blocks api requests until two-factor authentication is on', function () {
    config(['headless.two_factor.required' => true]);
    apiAs(User::factory()->create());

    getJson('/api/v1/tokens')->assertForbidden()->assertExactJson(['message' => __('headless::two_factor.required')]);
    getJson('/api/v1/me')->assertOk();
    getJson('/api/v1/me/two-factor')->assertOk();
    deleteJson('/api/v1/auth/token')->assertNoContent();
});

it('does nothing while not required', function () {
    actingAs(User::factory()->create())->get(route('headless.dashboard'))->assertOk();
});
