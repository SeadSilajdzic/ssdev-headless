<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Ssdev\Headless\Http\Controllers\DashboardController;
use Workbench\App\Models\TenantUser;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('signs in only to the account of the host\'s tenant', function () {
    $acme = makeTenant('acme');
    tenantUser($acme, ['email' => 'ada@example.com', 'password' => 'acme-password']);
    tenantUser(makeTenant('globex'), ['email' => 'ada@example.com', 'password' => 'globex-password']);

    post(tenantUrl($acme, 'login'), ['email' => 'ada@example.com', 'password' => 'globex-password'])->assertSessionHasErrors('email');
    $this->assertGuest();

    post(tenantUrl($acme, 'login'), ['email' => 'ada@example.com', 'password' => 'acme-password'])->assertRedirect();
    expect(Auth::user()?->getAttribute('tenant_id'))->toBe($acme->id);
});

it('keeps central accounts off tenants and tenant accounts off the central domain', function () {
    $acme = makeTenant('acme');
    TenantUser::factory()->create(['email' => 'central@example.com']);
    tenantUser($acme, ['email' => 'tenant@example.com']);

    post(tenantUrl($acme, 'login'), ['email' => 'central@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
    post('http://localhost/login', ['email' => 'tenant@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('treats a session from another tenant as a guest', function () {
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');
    tenantUser($acme, ['email' => 'ada@example.com']);

    post(tenantUrl($acme, 'login'), ['email' => 'ada@example.com', 'password' => 'password'])->assertRedirect();
    Auth::forgetGuards();

    get(tenantUrl($globex, DashboardController::PATH))->assertRedirect(tenantUrl($globex, 'login'));
    Auth::forgetGuards();

    // The session was emptied on the other tenant, so it does not sign in again back home either.
    get(tenantUrl($acme, DashboardController::PATH))->assertRedirect(tenantUrl($acme, 'login'));
});

it('refuses an API token on another tenant', function () {
    $acme = makeTenant('acme');
    $token = tenantUser($acme)->createToken('cli', ['*'])->plainTextToken;

    $this->withToken($token)->getJson(tenantUrl($acme, 'api/v1/me'))->assertOk();
    Auth::forgetGuards();

    $this->withToken($token)->getJson(tenantUrl(makeTenant('globex'), 'api/v1/me'))->assertUnauthorized();
});

it('keeps registration closed inside tenants unless configured', function () {
    $acme = makeTenant('acme');

    get(tenantUrl($acme, 'register'))->assertNotFound();
    get('http://localhost/register')->assertNotFound();

    config(['headless.tenancy.registration' => true]);

    get(tenantUrl($acme, 'register'))->assertOk();
});

it('registers the same email in two tenants', function () {
    config(['headless.tenancy.registration' => true]);
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');
    $form = ['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'S3cure-password!', 'password_confirmation' => 'S3cure-password!'];

    post(tenantUrl($acme, 'register'), $form)->assertRedirect();
    Auth::forgetGuards();
    post(tenantUrl($globex, 'register'), $form)->assertRedirect();

    expect(DB::table('users')->where('email', 'ada@example.com')->pluck('tenant_id')->sort()->values()->all())->toBe([$acme->id, $globex->id]);
});

it('checks a taken email only within the tenant', function () {
    $globex = makeTenant('globex');
    tenantUser(makeTenant('acme'), ['email' => 'grace@example.com']);
    tenantUser($globex, ['email' => 'taken@example.com']);
    $ada = tenantUser($globex, ['email' => 'ada@example.com']);
    $change = fn (string $email) => actingAs($ada)->put(tenantUrl($globex, 'settings/profile'), ['name' => 'Ada', 'email' => $email, 'current_password' => 'password']);

    $change('grace@example.com')->assertSessionHasNoErrors();
    $change('taken@example.com')->assertSessionHasErrors('email');
});

it('creates a central admin with an email a tenant already uses', function () {
    tenantUser(makeTenant('acme'), ['email' => 'grace@example.com']);

    artisan('headless:create-admin', ['--name' => 'Grace', '--email' => 'grace@example.com'])
        ->expectsQuestion('Password', 'S3cure-password!')
        ->expectsQuestion('Confirm password', 'S3cure-password!')
        ->assertSuccessful();

    expect(TenantUser::query()->where('email', 'grace@example.com')->value('tenant_id'))->toBe(0);
});
