<?php

use Illuminate\Support\Facades\Validator;
use Ssdev\Headless\Features\Tenancy\Criteria\FilterTenantsByStatus;
use Ssdev\Headless\Features\Tenancy\Criteria\SearchTenants;
use Ssdev\Headless\Features\Tenancy\Criteria\SortTenants;
use Ssdev\Headless\Features\Tenancy\Rules\AvailableSubdomain;
use Ssdev\Headless\Features\Tenancy\TenantRepository;
use Ssdev\Headless\Features\Tenancy\TenantStatus;

it('searches, filters and sorts tenants', function () {
    makeTenant('acme');
    makeTenant('globex', ['suspended_at' => now()]);
    makeTenant('initech');
    $names = fn (array $criteria): array => app(TenantRepository::class)->paginate($criteria)->pluck('name')->all();

    expect($names([new SearchTenants('glo')]))->toBe(['Globex'])
        ->and($names([new FilterTenantsByStatus(TenantStatus::Suspended)]))->toBe(['Globex'])
        ->and($names([new FilterTenantsByStatus(TenantStatus::Active), new SortTenants('name', 'asc')]))->toBe(['Acme', 'Initech']);
});

it('refuses invalid subdomains', function (string $subdomain) {
    makeTenant('taken');

    expect(Validator::make(['subdomain' => $subdomain], ['subdomain' => [app(AvailableSubdomain::class)]])->fails())->toBeTrue();
})->with(['Acme Corp', 'Acme', '-acme', 'acme-', 'ab', 'acme.corp', 'www', 'admin', 'taken', str_repeat('a', 64)]);

it('accepts a free subdomain', function (string $subdomain) {
    expect(Validator::make(['subdomain' => $subdomain], ['subdomain' => [app(AvailableSubdomain::class)]])->passes())->toBeTrue();
})->with(['acme', 'acme-corp', 'a1b', str_repeat('a', 63)]);

it('reads the owner only from the tenant\'s own accounts', function () {
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');
    $owner = tenantUser($acme);
    $acme->forceFill(['owner_id' => $owner->getKey()])->save();
    // owner_id has no foreign key, so it may point at an account that is not the tenant's.
    $globex->forceFill(['owner_id' => $owner->getKey()])->save();

    $owners = app(TenantRepository::class)->paginate([new SortTenants('name', 'asc')])->map(fn ($tenant) => $tenant->owner?->getKey())->all();

    expect($owners)->toBe([$owner->getKey(), null])
        ->and($globex->fresh()?->owner)->toBeNull();
});
