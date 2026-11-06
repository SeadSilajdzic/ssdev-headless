<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Features\Tenancy\Notifications\TenantOwnerInvitation;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Ssdev\Headless\Features\Tenancy\TenantOwnerService;
use Ssdev\Headless\Features\Tenancy\TenantPermission;
use Ssdev\Headless\Features\Tenancy\TenantService;
use Ssdev\Headless\Permissions\PermissionSynchronizer;
use Ssdev\Headless\Support\Flash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\travel;

beforeEach(function () {
    confirmPassword();
    Notification::fake();
});

it('lists tenants to central admins with tenants.view', function () {
    $viewer = centralAdmin(TenantPermission::View);
    ownedTenant('acme');
    app(TenantService::class)->suspend($viewer, ownedTenant('globex'));

    actingAs($viewer)->get(route('headless.tenants.index', ['search' => 'glo', 'status' => 'suspended']))->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Tenants/Index'), false)
        ->where('tenants.meta.total', 1)
        ->where('tenants.data.0.subdomain', 'globex')
        ->where('tenants.data.0.url', 'http://globex.localhost')
        ->where('tenants.data.0.status', 'suspended')
        ->where('tenants.data.0.owner.email', 'owner@globex.test')
        ->where('tenants.data.0.owner.accepted', false)
        ->where('filters.search', 'glo')
        ->where('filters.status', 'suspended')
        ->where('can.manage', false));
});

it('forbids pages without tenants.view and changes without tenants.manage', function () {
    $tenant = makeTenant('acme');
    $outsider = centralAdmin();
    $viewer = centralAdmin(TenantPermission::View);

    actingAs($outsider)->get(route('headless.tenants.index'))->assertForbidden();
    actingAs($outsider)->get(route('headless.tenants.show', $tenant->id))->assertForbidden();

    actingAs($viewer)->get(route('headless.tenants.show', $tenant->id))->assertOk();
    actingAs($viewer)->get(route('headless.tenants.create'))->assertForbidden();
    actingAs($viewer)->post(route('headless.tenants.store'), ['name' => 'Globex', 'subdomain' => 'globex', 'owner_email' => 'owner@globex.test'])->assertForbidden();
    actingAs($viewer)->put(route('headless.tenants.update', $tenant->id), ['name' => 'Renamed'])->assertForbidden();
    actingAs($viewer)->post(route('headless.tenants.suspension.store', $tenant->id))->assertForbidden();
    actingAs($viewer)->delete(route('headless.tenants.suspension.destroy', $tenant->id))->assertForbidden();
    actingAs($viewer)->post(route('headless.tenants.owner-invitation.store', $tenant->id))->assertForbidden();
    actingAs($viewer)->delete(route('headless.tenants.destroy', $tenant->id), ['subdomain' => 'acme'])->assertForbidden();

    expect($tenant->fresh()?->name)->toBe('Acme')
        ->and($tenant->fresh()?->isSuspended())->toBeFalse()
        ->and(Tenant::query()->count())->toBe(1);
});

it('creates a tenant from the page', function () {
    $admin = tenantsManager();

    actingAs($admin)->get(route('headless.tenants.create'))->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Tenants/Create'), false)
        ->where('baseDomain', 'localhost'));
    $response = actingAs($admin)->post(route('headless.tenants.store'), ['name' => 'Acme', 'subdomain' => ' ACME ', 'owner_email' => 'Owner@Acme.test']);

    $tenant = Tenant::query()->where('subdomain', 'acme')->firstOrFail();
    $owner = app(TenantOwnerService::class)->owner($tenant);
    $response->assertRedirect(route('headless.tenants.show', $tenant->id))->assertSessionHas(Flash::SUCCESS);
    expect($owner?->getAttribute('email'))->toBe('owner@acme.test');
    Notification::assertSentTo($owner, TenantOwnerInvitation::class);

    actingAs($admin)->post(route('headless.tenants.store'), ['name' => 'Www', 'subdomain' => 'www', 'owner_email' => 'not-an-email'])
        ->assertSessionHasErrors(['subdomain', 'owner_email']);
    actingAs($admin)->post(route('headless.tenants.store'), ['name' => 'Acme again', 'subdomain' => 'acme', 'owner_email' => 'other@acme.test'])
        ->assertSessionHasErrors('subdomain');
    expect(Tenant::query()->count())->toBe(1);
});

it('renames but never changes the subdomain', function () {
    $admin = tenantsManager();
    $tenant = makeTenant('acme');

    actingAs($admin)->put(route('headless.tenants.update', $tenant->id), ['name' => 'Acme Corp', 'subdomain' => 'globex'])
        ->assertRedirect()
        ->assertSessionHas(Flash::SUCCESS);
    actingAs($admin)->put(route('headless.tenants.update', $tenant->id), ['name' => ''])->assertSessionHasErrors('name');

    expect($tenant->fresh()?->name)->toBe('Acme Corp')
        ->and($tenant->fresh()?->subdomain)->toBe('acme');
});

it('suspends, resumes and deletes from the page', function () {
    $admin = tenantsManager();
    $tenant = makeTenant('acme');

    actingAs($admin)->post(route('headless.tenants.suspension.store', $tenant->id))->assertSessionHas(Flash::SUCCESS);
    expect($tenant->fresh()?->isSuspended())->toBeTrue();

    actingAs($admin)->delete(route('headless.tenants.suspension.destroy', $tenant->id))->assertSessionHas(Flash::SUCCESS);
    expect($tenant->fresh()?->isSuspended())->toBeFalse();

    actingAs($admin)->delete(route('headless.tenants.destroy', $tenant->id), ['subdomain' => 'acm'])->assertSessionHasErrors('subdomain');
    actingAs($admin)->delete(route('headless.tenants.destroy', $tenant->id))->assertSessionHasErrors('subdomain');
    expect($tenant->fresh())->not->toBeNull();

    actingAs($admin)->delete(route('headless.tenants.destroy', $tenant->id), ['subdomain' => 'acme'])
        ->assertRedirect(route('headless.tenants.index'))
        ->assertSessionHas(Flash::SUCCESS);
    expect($tenant->fresh())->toBeNull();
});

it('resends the owner invitation', function () {
    $admin = tenantsManager();
    $tenant = ownedTenant('acme');
    $owner = app(TenantOwnerService::class)->owner($tenant);

    actingAs($admin)->get(route('headless.tenants.show', $tenant->id))->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Tenants/Show'), false)
        ->where('tenant.subdomain', 'acme')
        ->where('tenant.owner.email', 'owner@acme.test')
        ->where('tenant.owner.accepted', false)
        ->where('can.manage', true));
    travel(2)->minutes();
    actingAs($admin)->post(route('headless.tenants.owner-invitation.store', $tenant->id))->assertSessionHas(Flash::SUCCESS);

    Notification::assertSentToTimes($owner, TenantOwnerInvitation::class, 2);
});

it('throttles owner invitations', function () {
    config(['headless.rate_limits.owner_invitations' => 1]);
    $admin = tenantsManager();
    $tenant = ownedTenant('acme');

    actingAs($admin)->post(route('headless.tenants.owner-invitation.store', $tenant->id));

    actingAs($admin)->post(route('headless.tenants.owner-invitation.store', $tenant->id))->assertTooManyRequests();
});

it('throttles creating tenants, because each one mails its owner', function () {
    config(['headless.rate_limits.owner_invitations' => 1]);
    $admin = tenantsManager();

    actingAs($admin)->post(route('headless.tenants.store'), ['name' => 'Acme', 'subdomain' => 'acme', 'owner_email' => 'owner@acme.test'])->assertRedirect();

    actingAs($admin)->post(route('headless.tenants.store'), ['name' => 'Globex', 'subdomain' => 'globex', 'owner_email' => 'owner@globex.test'])->assertTooManyRequests();
    expect(Tenant::query()->count())->toBe(1);
});

it('hides the pages on tenant hosts', function () {
    app(PermissionSynchronizer::class)->sync();
    $acme = makeTenant('acme');
    $member = tenantUser($acme);
    $acme->execute(fn () => $member->givePermissionTo(TenantPermission::names()));

    actingAs($member)->get(tenantUrl($acme, 'tenants'))->assertNotFound();
    actingAs($member)->get(tenantUrl($acme, "tenants/{$acme->id}"))->assertNotFound();
    actingAs($member)->delete(tenantUrl($acme, "tenants/{$acme->id}"), ['subdomain' => 'acme'])->assertNotFound();
    Auth::forgetGuards();

    get(tenantUrl($acme, 'tenants'))->assertNotFound();
    expect($acme->fresh())->not->toBeNull();
});

it('shows the tenants link only to tenants.view', function () {
    expect(navigationKeys(centralAdmin(TenantPermission::View)))->toContain('tenants')
        ->and(navigationKeys(centralAdmin()))->not->toContain('tenants');
});
