<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Features\Roles\RoleGuard;
use Ssdev\Headless\Features\Roles\RoleRepository;
use Ssdev\Headless\Features\Roles\RolesModule;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Ssdev\Headless\Features\Tenancy\TenantOwnerService;
use Ssdev\Headless\Features\Tenancy\TenantPermission;
use Ssdev\Headless\Features\Users\UserPermission;
use Ssdev\Headless\Permissions\PermissionRegistry;
use Ssdev\Headless\Permissions\PermissionSynchronizer;
use Workbench\App\Models\TenantUser;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

beforeEach(fn () => app(PermissionSynchronizer::class)->sync());

function tenantAdmin(Tenant $tenant, UserPermission ...$permissions): TenantUser
{
    $admin = tenantUser($tenant);
    $tenant->execute(fn () => $admin->givePermissionTo(array_map(fn (UserPermission $permission) => $permission->value, $permissions)));

    return $admin;
}

it('gives the owner the tenant\'s Administrator role with every tenant permission', function () {
    Notification::fake();
    $tenant = ownedTenant('acme');
    $owner = app(TenantOwnerService::class)->owner($tenant) ?? throw new LogicException('No owner.');

    [$roles, $role, $direct] = $tenant->execute(fn (): array => [
        app(RoleRepository::class)->assignedTo($owner)->pluck('name')->all(),
        app(RoleGuard::class)->roles()->where('name', RolesModule::ADMIN_ROLE)->with('permissions')->first(),
        $owner->getDirectPermissions()->pluck('name')->all(),
    ]);

    expect($roles)->toBe([RolesModule::ADMIN_ROLE])
        ->and((int) $role?->getAttribute('tenant_id'))->toBe($tenant->id)
        ->and($role?->permissions->pluck('name')->all())->toEqualCanonicalizing(array_diff(app(PermissionRegistry::class)->names(), TenantPermission::names()))
        ->and($direct)->toBe([])
        ->and(app(RoleGuard::class)->roles()->where('name', RolesModule::ADMIN_ROLE)->exists())->toBeFalse();
});

it('lists and manages only the tenant\'s users', function () {
    $acme = makeTenant('acme');
    $admin = tenantAdmin($acme, UserPermission::View, UserPermission::Delete);
    tenantUser($acme);
    $central = TenantUser::factory()->create();
    $globex = tenantUser(makeTenant('globex'));
    apiAs($admin);

    getJson(tenantUrl($acme, 'api/v1/users'))->assertOk()->assertJsonPath('meta.total', 2);
    getJson(tenantUrl($acme, "api/v1/users/{$central->id}"))->assertNotFound();
    getJson(tenantUrl($acme, "api/v1/users/{$globex->id}"))->assertNotFound();
    deleteJson(tenantUrl($acme, "api/v1/users/{$globex->id}"))->assertNotFound();

    expect(DB::table('users')->whereIn('id', [$central->id, $globex->id])->count())->toBe(2);
});

it('keeps the last user manager per tenant', function () {
    $acme = makeTenant('acme');
    $admin = tenantAdmin($acme, UserPermission::View, UserPermission::Delete);
    $manager = tenantAdmin($acme, UserPermission::Block);
    TenantUser::factory()->create()->givePermissionTo(UserPermission::Block->value);
    apiAs($admin);

    deleteJson(tenantUrl($acme, "api/v1/users/{$manager->id}"))->assertUnprocessable()->assertJsonValidationErrors('user');
});

it('assigns only the tenant\'s roles', function () {
    $acme = makeTenant('acme');
    $globexRole = makeTenant('globex')->execute(fn (): Role => app(RoleGuard::class)->roles()->create(['name' => 'Editor', 'guard_name' => app(RoleGuard::class)->name()]));
    $admin = tenantAdmin($acme, UserPermission::View);
    $acme->execute(fn () => $admin->givePermissionTo(['roles.view', 'roles.manage']));
    $member = tenantUser($acme);
    apiAs($admin);

    putJson(tenantUrl($acme, "api/v1/users/{$member->id}/roles"), ['roles' => [$globexRole->id]])->assertUnprocessable()->assertJsonValidationErrors('roles');
    expect($acme->execute(fn () => app(RoleRepository::class)->assignedTo($member)->count()))->toBe(0);
});
