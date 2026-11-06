<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Ssdev\Headless\Http\Controllers\DashboardController;
use Workbench\App\Models\TenantUser;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

it('returns to the page a guest asked for after signing in on the tenant', function () {
    Route::get('/reports', fn () => 'reports')->middleware(['web', 'auth']);
    $acme = makeTenant('acme');
    $ada = tenantUser($acme);

    get(tenantUrl($acme, 'reports'))->assertRedirect(tenantUrl($acme, 'login'));

    post(tenantUrl($acme, 'login'), ['email' => $ada->email, 'password' => 'password'])->assertRedirect(tenantUrl($acme, 'reports'));
});

it('returns to the page after confirming the password on the tenant', function () {
    Route::get('/billing', fn () => 'billing')->middleware(['web', 'auth', 'password.confirm']);
    $acme = makeTenant('acme');
    $confirm = $acme->execute(fn () => route('password.confirm.store'));

    actingAs(tenantUser($acme))->get(tenantUrl($acme, 'billing'))->assertRedirect();

    post($confirm, ['password' => 'password'])->assertRedirect(tenantUrl($acme, 'billing'));
});

it('returns to the page after verifying the email on the tenant', function () {
    Route::get('/reports', fn () => 'reports')->middleware(['web', 'auth', 'verified']);
    $acme = makeTenant('acme');
    $ada = tenantUser($acme, ['email_verified_at' => null]);
    $link = $acme->execute(fn () => URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $ada->id, 'hash' => sha1($ada->email)]));

    actingAs($ada)->get(tenantUrl($acme, 'reports'))->assertRedirect();

    get($link)->assertRedirect(tenantUrl($acme, 'reports'));
});

it('accepts the tenant\'s host in any letter case and on any port', function () {
    $acme = makeTenant('acme');
    $ada = tenantUser($acme);

    withSession(['url.intended' => 'http://ACME.localhost:8080/reports'])
        ->post(tenantUrl($acme, 'login'), ['email' => $ada->email, 'password' => 'password'])
        ->assertRedirect('http://ACME.localhost:8080/reports');
});

it('refuses another tenant\'s host and the central domain inside a tenant', function (string $intended) {
    $acme = makeTenant('acme');
    makeTenant('globex');
    $ada = tenantUser($acme);

    withSession(['url.intended' => $intended])
        ->post(tenantUrl($acme, 'login'), ['email' => $ada->email, 'password' => 'password'])
        ->assertRedirect(tenantUrl($acme, DashboardController::PATH));
})->with(['http://globex.localhost/reports', 'http://localhost/reports', 'http://evil.localhost.test/reports']);

it('refuses a host that hides behind the tenant\'s host', function () {
    $acme = makeTenant('acme');
    $ada = tenantUser($acme);

    withSession(['url.intended' => 'https://evil.test\@acme.localhost/steal'])
        ->post(tenantUrl($acme, 'login'), ['email' => $ada->email, 'password' => 'password'])
        ->assertRedirect(tenantUrl($acme, DashboardController::PATH));
});

it('refuses a tenant\'s host on the central domain', function () {
    $acme = makeTenant('acme');
    $admin = TenantUser::factory()->create();

    withSession(['url.intended' => tenantUrl($acme, 'reports')])
        ->post('http://localhost/login', ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect('http://localhost/'.DashboardController::PATH);
});

it('trusts the central domain rather than the app URL on the central level', function () {
    config(['headless.tenancy.central_domain' => 'platform.test']);
    $admin = TenantUser::factory()->create();

    withSession(['url.intended' => 'http://platform.test/reports'])
        ->post('http://platform.test/login', ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect('http://platform.test/reports');
});
