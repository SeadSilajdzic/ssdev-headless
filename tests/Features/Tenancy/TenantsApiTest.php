<?php

use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Ssdev\Headless\Features\Tenancy\Notifications\TenantOwnerInvitation;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Ssdev\Headless\Features\Tenancy\TenantOwnerService;
use Ssdev\Headless\Features\Tenancy\TenantPermission;
use Ssdev\Headless\Features\Tenancy\TenantService;
use Ssdev\Headless\Permissions\PermissionSynchronizer;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\travel;

beforeEach(fn () => Notification::fake());

it('lists tenants with filters', function () {
    $viewer = apiAs(centralAdmin(TenantPermission::View));
    ownedTenant('acme');
    app(TenantService::class)->suspend($viewer, ownedTenant('globex'));

    getJson('/api/v1/tenants?search=glo')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.subdomain', 'globex');
    getJson('/api/v1/tenants?status=active')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.subdomain', 'acme');
    getJson('/api/v1/tenants?status=nope')->assertUnprocessable()->assertJsonValidationErrors('status');
});

it('creates, renames, suspends, resumes and deletes a tenant', function () {
    apiAs(tenantsManager());

    $id = postJson('/api/v1/tenants', ['name' => 'Acme', 'subdomain' => 'ACME', 'owner_email' => 'Owner@Acme.test'])
        ->assertCreated()
        ->assertJsonPath('data.subdomain', 'acme')
        ->assertJsonPath('data.owner.email', 'owner@acme.test')
        ->assertJsonPath('data.owner.accepted', false)
        ->json('data.id');

    getJson("/api/v1/tenants/{$id}")->assertOk()->assertJsonPath('data.name', 'Acme');
    putJson("/api/v1/tenants/{$id}", ['name' => 'Acme Corp', 'subdomain' => 'globex'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Acme Corp')
        ->assertJsonPath('data.subdomain', 'acme');
    postJson("/api/v1/tenants/{$id}/suspension")->assertOk()->assertJsonPath('data.status', 'suspended');
    deleteJson("/api/v1/tenants/{$id}/suspension")->assertOk()->assertJsonPath('data.status', 'active');
    deleteJson("/api/v1/tenants/{$id}", ['subdomain' => 'acm'])->assertUnprocessable()->assertJsonValidationErrors('subdomain');
    expect(Tenant::query()->whereKey($id)->exists())->toBeTrue();

    deleteJson("/api/v1/tenants/{$id}", ['subdomain' => 'acme'])->assertNoContent();
    expect(Tenant::query()->whereKey($id)->exists())->toBeFalse();
});

it('rejects a bad or taken subdomain', function () {
    apiAs(tenantsManager());
    ownedTenant('acme');

    foreach (['www', '-acme', 'a b', 'acme'] as $subdomain) {
        postJson('/api/v1/tenants', ['name' => 'Acme', 'subdomain' => $subdomain, 'owner_email' => 'owner@example.test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('subdomain');
    }

    expect(Tenant::query()->count())->toBe(1);
});

it('sends the owner invitation again', function () {
    apiAs(tenantsManager());
    $tenant = ownedTenant('acme');
    travel(2)->minutes();

    postJson("/api/v1/tenants/{$tenant->id}/owner-invitation")->assertNoContent();

    Notification::assertSentToTimes(app(TenantOwnerService::class)->owner($tenant), TenantOwnerInvitation::class, 2);
});

it('forbids without tenants permissions or the token ability', function () {
    $tenant = makeTenant('acme');

    apiAs(centralAdmin());
    getJson('/api/v1/tenants')->assertForbidden()->assertJsonStructure(['message']);

    apiAs(tenantsManager(), [TenantPermission::View->value]);
    getJson("/api/v1/tenants/{$tenant->id}")->assertOk();
    putJson("/api/v1/tenants/{$tenant->id}", ['name' => 'Renamed'])->assertForbidden();
    postJson('/api/v1/tenants', ['name' => 'Globex', 'subdomain' => 'globex', 'owner_email' => 'owner@globex.test'])->assertForbidden();
});

it('answers 404 on a tenant host', function () {
    app(PermissionSynchronizer::class)->sync();
    $acme = makeTenant('acme');
    $member = tenantUser($acme);
    $acme->execute(fn () => $member->givePermissionTo(TenantPermission::names()));
    Sanctum::actingAs($member, ['*']);

    getJson(tenantUrl($acme, 'api/v1/tenants'))->assertNotFound();
    deleteJson(tenantUrl($acme, "api/v1/tenants/{$acme->id}"), ['subdomain' => 'acme'])->assertNotFound();
    expect($acme->fresh())->not->toBeNull();
});
