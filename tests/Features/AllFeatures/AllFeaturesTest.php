<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Features\Roles\RoleGuard;
use Ssdev\Headless\Features\Roles\RolesModule;
use Ssdev\Headless\Features\Teams\TeamService;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Ssdev\Headless\Features\Tenancy\TenantOwnerPermissions;
use Ssdev\Headless\Features\TwoFactor\TwoFactorService;
use Ssdev\Headless\Http\Controllers\DashboardController;
use Ssdev\Headless\Permissions\PermissionSynchronizer;
use Workbench\App\Models\TenantUser;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;

beforeEach(fn () => app(PermissionSynchronizer::class)->sync());

function tenantOwnerOf(Tenant $tenant): TenantUser
{
    $owner = tenantUser($tenant);
    $tenant->execute(fn () => app(TenantOwnerPermissions::class)->grant($owner));

    return $owner;
}

/**
 * @return list<string>
 */
function navigationOn(string $url, TenantUser $user): array
{
    $keys = [];

    actingAs($user)->get($url)->assertInertia(function (AssertableInertia $page) use (&$keys) {
        $keys = collect($page->toArray()['props']['headless']['navigation'])->flatMap(fn (array $section) => array_column($section['items'], 'key'))->all();
    });

    return $keys;
}

it('shows a tenant owner the tenant\'s systems and only central admins the tenants panel', function () {
    $acme = makeTenant('acme');

    expect(navigationOn(tenantUrl($acme, DashboardController::PATH), tenantOwnerOf($acme)))->toContain('users', 'roles', 'two-factor')->not->toContain('tenants')
        ->and(navigationOn(url(DashboardController::PATH), tenantsManager()))->toContain('tenants');
});

it('challenges a tenant account with two-factor authentication on the tenant\'s host', function () {
    $acme = makeTenant('acme');
    $ada = tenantUser($acme);
    $acme->execute(function () use ($ada): void {
        app(TwoFactorService::class)->enable($ada);
        app(TwoFactorService::class)->confirm($ada, currentOtp($ada));
        // Confirming used up the current code in the tenant's cache.
        Cache::flush();
    });
    $ada = $ada->fresh();

    post(tenantUrl($acme, 'login'), ['email' => $ada->email, 'password' => 'password'])->assertRedirect(tenantUrl($acme, 'two-factor-challenge'));
    post(tenantUrl($acme, 'two-factor-challenge'), ['code' => currentOtp($ada)])->assertRedirect(tenantUrl($acme, DashboardController::PATH));

    assertAuthenticatedAs($ada);
});

it('returns to the page asked for after the two-factor challenge on the tenant\'s host', function () {
    Route::get('/reports', fn () => 'reports')->middleware(['web', 'auth']);
    $acme = makeTenant('acme');
    $ada = tenantUser($acme);
    $acme->execute(function () use ($ada): void {
        app(TwoFactorService::class)->enable($ada);
        app(TwoFactorService::class)->confirm($ada, currentOtp($ada));
        // Confirming used up the current code in the tenant's cache.
        Cache::flush();
    });
    $ada = $ada->fresh();

    get(tenantUrl($acme, 'reports'))->assertRedirect(tenantUrl($acme, 'login'));
    post(tenantUrl($acme, 'login'), ['email' => $ada->email, 'password' => 'password'])->assertRedirect(tenantUrl($acme, 'two-factor-challenge'));

    post(tenantUrl($acme, 'two-factor-challenge'), ['code' => currentOtp($ada)])->assertRedirect(tenantUrl($acme, 'reports'));
});

it('keeps an owner\'s users, roles and teams inside the tenant', function () {
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');
    $owner = tenantOwnerOf($acme);
    $acme->execute(fn () => app(TeamService::class)->create($owner, 'Acme team'));
    $globexOwner = tenantOwnerOf($globex);
    $globex->execute(function () use ($globexOwner): void {
        app(RoleGuard::class)->roles()->create(['name' => 'Editor', 'guard_name' => app(RoleGuard::class)->name()]);
        app(TeamService::class)->create($globexOwner, 'Globex team');
    });
    app(RoleGuard::class)->roles()->create(['name' => 'Auditor', 'guard_name' => app(RoleGuard::class)->name()]);
    app(TeamService::class)->create(TenantUser::factory()->create(), 'Platform');
    apiAs($owner);

    getJson(tenantUrl($acme, 'api/v1/users'))->assertOk()->assertJsonPath('meta.total', 1);
    getJson(tenantUrl($acme, 'api/v1/roles'))->assertOk()->assertJsonPath('data.*.name', [RolesModule::ADMIN_ROLE]);
    getJson(tenantUrl($acme, 'api/v1/teams'))->assertOk()->assertJsonPath('data.*.name', ['Acme team']);
});
