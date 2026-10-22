<?php

use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('shows the suspension page on a suspended tenant', function () {
    $acme = makeTenant('acme', ['suspended_at' => now()]);

    get(tenantUrl($acme, 'login'))
        ->assertForbidden()
        ->assertInertia(fn (AssertableInertia $page) => $page->component(headlessPage('Tenancy/Suspended'), false));
});

it('refuses API requests of a suspended tenant', function () {
    $acme = makeTenant('acme');
    $token = tenantUser($acme)->createToken('cli', ['*'])->plainTextToken;
    $acme->forceFill(['suspended_at' => now()])->save();

    $this->withToken($token)->getJson(tenantUrl($acme, 'api/v1/me'))
        ->assertForbidden()
        ->assertExactJson(['message' => __('headless::tenancy.suspended.description')]);
});

it('refuses signing in to a suspended tenant', function () {
    $acme = makeTenant('acme', ['suspended_at' => now()]);
    tenantUser($acme, ['email' => 'ada@example.com']);

    post(tenantUrl($acme, 'login'), ['email' => 'ada@example.com', 'password' => 'password'])->assertForbidden();
    $this->assertGuest();
});

it('serves other tenants and the central domain as usual', function () {
    makeTenant('acme', ['suspended_at' => now()]);

    get(tenantUrl(makeTenant('globex'), 'login'))->assertOk();
    get('http://localhost/login')->assertOk();
});
