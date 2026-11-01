<?php

use Illuminate\Support\Facades\Notification;
use Ssdev\ApiContracts\Testing\InteractsWithApiContract;
use Ssdev\Headless\Features\Tenancy\TenantService;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(InteractsWithApiContract::class);

beforeEach(function () {
    config(['api-contract.snapshot_dir' => packageSnapshotDirectory(), 'api-contract.strict' => true]);
    Notification::fake();
    apiAs(tenantsManager());
});

it('GET /api/v1/tenants matches contract', function () {
    ownedTenant('acme');

    $this->assertMatchesApiContract('GET_api_v1_tenants', getJson('/api/v1/tenants')->assertOk()->json());
});

it('POST /api/v1/tenants matches contract', function () {
    $response = postJson('/api/v1/tenants', ['name' => 'Acme', 'subdomain' => 'acme', 'owner_email' => 'owner@acme.test'])->assertCreated();

    $this->assertMatchesApiContract('POST_api_v1_tenants', $response->json());
});

it('GET /api/v1/tenants/{tenant} matches contract', function () {
    $tenant = ownedTenant('acme');

    $this->assertMatchesApiContract('GET_api_v1_tenants_show', getJson("/api/v1/tenants/{$tenant->id}")->assertOk()->json());
});

it('PUT /api/v1/tenants/{tenant} matches contract', function () {
    $tenant = ownedTenant('acme');

    $this->assertMatchesApiContract('PUT_api_v1_tenants_show', putJson("/api/v1/tenants/{$tenant->id}", ['name' => 'Acme Corp'])->assertOk()->json());
});

it('POST /api/v1/tenants/{tenant}/suspension matches contract', function () {
    $tenant = ownedTenant('acme');

    $this->assertMatchesApiContract('POST_api_v1_tenants_show_suspension', postJson("/api/v1/tenants/{$tenant->id}/suspension")->assertOk()->json());
});

it('DELETE /api/v1/tenants/{tenant}/suspension matches contract', function () {
    $tenant = ownedTenant('acme');
    app(TenantService::class)->suspend(tenantsManager(), $tenant);

    $this->assertMatchesApiContract('DELETE_api_v1_tenants_show_suspension', deleteJson("/api/v1/tenants/{$tenant->id}/suspension")->assertOk()->json());
});

it('POST /api/v1/tenants/{tenant}/owner-invitation answers without content', function () {
    $tenant = ownedTenant('acme');
    $this->travel(2)->minutes();

    postJson("/api/v1/tenants/{$tenant->id}/owner-invitation")->assertNoContent();
});

it('DELETE /api/v1/tenants/{tenant} answers without content', function () {
    $tenant = ownedTenant('acme');

    deleteJson("/api/v1/tenants/{$tenant->id}", ['subdomain' => 'acme'])->assertNoContent();
});
