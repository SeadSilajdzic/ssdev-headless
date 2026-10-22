<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Ssdev\Headless\Features\Tenancy\Events\TenantCreated;
use Ssdev\Headless\Features\Tenancy\TenantOwnerService;
use Ssdev\Headless\Features\Tenancy\TenantPermission;
use Ssdev\Headless\Features\Tenancy\TenantService;
use Ssdev\Headless\Permissions\PermissionRegistry;
use Ssdev\Headless\Tests\Fixtures\Permissions\FakePermission;
use Workbench\App\Models\TenantUser;

it('creates a tenant with its owner', function () {
    Event::fake([TenantCreated::class]);
    $admin = TenantUser::factory()->create();

    $tenant = app(TenantService::class)->create($admin, 'Acme', 'ACME', 'Owner@Acme.test');
    $owner = app(TenantOwnerService::class)->owner($tenant);

    expect($tenant->subdomain)->toBe('acme')
        ->and($owner?->getAttribute('email'))->toBe('owner@acme.test')
        ->and($owner?->getAttribute('tenant_id'))->toBe($tenant->id)
        ->and($owner?->getAttribute('email_verified_at'))->toBeNull()
        ->and($tenant->fresh()?->owner_id)->toBe($owner?->getKey())
        ->and(TenantUser::query()->where('email', 'owner@acme.test')->exists())->toBeFalse();
    Event::assertDispatched(TenantCreated::class, fn (TenantCreated $event) => $event->tenant->is($tenant) && $event->actor->is($admin));
});

it('gives the owner every tenant permission and no central one', function () {
    usePermissions(FakePermission::class);

    $tenant = app(TenantService::class)->create(TenantUser::factory()->create(), 'Acme', 'acme', 'owner@acme.test');
    $owner = app(TenantOwnerService::class)->owner($tenant);

    $granted = $tenant->execute(fn () => $owner?->getAllPermissions()->pluck('name')->sort()->values()->all());
    $expected = collect(app(PermissionRegistry::class)->names())->diff(TenantPermission::names())->sort()->values()->all();

    expect($expected)->toBe(['reports.export', 'reports.view'])
        ->and($granted)->toBe($expected);
});

it('answers a parallel create of the same subdomain with a validation error', function () {
    // As if another request created it after this one's validation passed.
    makeTenant('acme');

    expect(fn () => app(TenantService::class)->create(TenantUser::factory()->create(), 'Acme', 'acme', 'owner@acme.test'))->toThrow(failsOn('subdomain'))
        ->and(DB::table('users')->where('email', 'owner@acme.test')->exists())->toBeFalse();
});

it('renames a tenant', function () {
    $tenant = makeTenant('acme');

    app(TenantService::class)->rename($tenant, 'Acme Corporation');

    expect($tenant->fresh()?->name)->toBe('Acme Corporation');
});
