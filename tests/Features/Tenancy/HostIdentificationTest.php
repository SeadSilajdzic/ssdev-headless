<?php

use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Features\Tenancy\TenantContext;

use function Pest\Laravel\get;

// IdentifyTenant runs as global middleware, so the route needs no group.
beforeEach(fn () => Route::get('/tenant-key', fn () => (string) TenantContext::currentKey()));

it('makes the tenant of the subdomain current', function () {
    $acme = makeTenant('acme');

    get(tenantUrl($acme, 'tenant-key'))->assertOk()->assertContent((string) $acme->id);
});

it('stays central on the central domain', function () {
    makeTenant('acme');

    get('http://localhost/tenant-key')->assertOk()->assertContent((string) TenantContext::CENTRAL);
});

it('answers 404 for an unknown subdomain', function () {
    get('http://nope.localhost/tenant-key')->assertNotFound();
});

it('answers 404 for a nested subdomain, www and a foreign host', function (string $host) {
    makeTenant('acme');

    get("http://{$host}/tenant-key")->assertNotFound();
})->with(['a.acme.localhost', 'www.localhost', '127.0.0.1', 'example.com']);

it('ignores letter case and the port', function () {
    $acme = makeTenant('acme');

    get('http://ACME.localhost:8080/tenant-key')->assertOk()->assertContent((string) $acme->id);
});

it('builds tenant links outside a request', function () {
    $acme = makeTenant('acme');

    expect($acme->execute(fn () => route('login')))->toBe('http://acme.localhost/login')
        ->and(route('login'))->toBe('http://localhost/login');
});
