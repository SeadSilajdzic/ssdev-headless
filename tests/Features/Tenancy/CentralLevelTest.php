<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Features\Tenancy\Http\Middleware\EnsureCentralLevel;
use Ssdev\Headless\Features\Tenancy\TenantPermission;
use Ssdev\Headless\Permissions\PermissionSynchronizer;
use Workbench\App\Models\TenantUser;

use function Pest\Laravel\artisan;
use function Pest\Laravel\get;

it('never counts tenants permissions inside a tenant', function () {
    app(PermissionSynchronizer::class)->sync();
    $acme = makeTenant('acme');
    $member = tenantUser($acme);

    $allowed = $acme->execute(function () use ($member): bool {
        $member->givePermissionTo(TenantPermission::View->value);

        return $member->can(TenantPermission::View->value);
    });

    expect($allowed)->toBeFalse();
});

it('gives the central admin the tenants permissions', function () {
    artisan('headless:create-admin', ['--name' => 'Grace', '--email' => 'grace@example.com'])
        ->expectsQuestion('Password', 'S3cure-password!')
        ->expectsQuestion('Confirm password', 'S3cure-password!')
        ->assertSuccessful();

    expect(TenantUser::query()->where('email', 'grace@example.com')->firstOrFail()->can(TenantPermission::Manage->value))->toBeTrue();
});

it('closes sign-up on the central domain', function () {
    get('http://localhost/register')->assertNotFound();

    config(['headless.tenancy.registration' => true]);

    get(tenantUrl(makeTenant('acme'), 'register'))->assertOk();
});

it('answers 404 for central-only routes on a tenant host', function () {
    Route::middleware(EnsureCentralLevel::class)->get('/central-only', fn () => 'central');

    get(tenantUrl(makeTenant('acme'), 'central-only'))->assertNotFound();
    get('http://localhost/central-only')->assertOk();
});
