<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Features\Roles\RoleData;
use Ssdev\Headless\Features\Roles\RoleGuard;
use Ssdev\Headless\Features\Roles\RolePermission;
use Ssdev\Headless\Features\Roles\RoleRepository;
use Ssdev\Headless\Features\Roles\RoleService;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Ssdev\Headless\Permissions\PermissionSynchronizer;
use Ssdev\Headless\Tests\Fixtures\Permissions\FakePermission;
use Workbench\App\Models\TenantUser;

use function Pest\Laravel\postJson;

beforeEach(function () {
    usePermissions(FakePermission::class);
    app(PermissionSynchronizer::class)->sync();
});

/**
 * @param  list<string>  $permissions
 */
function levelRole(string $name, array $permissions = []): Role
{
    $role = app(RoleGuard::class)->roles()->create(['name' => $name, 'guard_name' => app(RoleGuard::class)->name()]);
    $role->givePermissionTo($permissions);

    return $role;
}

/**
 * @return list<string>
 */
function rolesUniqueColumns(): array
{
    return collect(Schema::getIndexes('roles'))->first(fn (array $index) => $index['unique'] && ! $index['primary'] && in_array('name', $index['columns'], true))['columns'] ?? [];
}

it('keeps role definitions apart per tenant and from the central level', function () {
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');
    levelRole('Editor');
    $acmeEditor = $acme->execute(fn () => levelRole('Editor'));
    $globex->execute(fn () => levelRole('Editor'));
    $roles = fn (): array => app(RoleRepository::class)->all()->map(fn (Role $role) => [$role->name, (int) $role->getAttribute('tenant_id')])->all();

    expect($roles())->toBe([['Editor', 0]])
        ->and($acme->execute($roles))->toBe([['Editor', $acme->id]])
        ->and($globex->execute($roles))->toBe([['Editor', $globex->id]])
        ->and(fn () => $globex->execute(fn () => app(RoleRepository::class)->findOrFail($acmeEditor->getKey())))->toThrow(ModelNotFoundException::class);
});

it('rejects a duplicate role name inside one tenant', function () {
    $acme = makeTenant('acme');
    $admin = tenantUser($acme);
    $create = fn () => $acme->execute(fn () => app(RoleService::class)->create($admin, new RoleData('Editor', [])));
    $create();

    expect($create)->toThrow(ValidationException::class);
});

it('allows a role name that another tenant or the central level uses', function () {
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');
    levelRole('Editor');
    $globex->execute(fn () => levelRole('Editor'));
    $admin = tenantUser($acme);
    $acme->execute(fn () => $admin->givePermissionTo([RolePermission::View->value, RolePermission::Manage->value]));
    apiAs($admin);

    postJson(tenantUrl($acme, 'api/v1/roles'), ['name' => 'Editor', 'permissions' => []])->assertCreated();
    postJson(tenantUrl($acme, 'api/v1/roles'), ['name' => 'Editor', 'permissions' => []])->assertUnprocessable()->assertJsonValidationErrors('name');
});

it('checks permissions against the current tenant\'s roles in one process', function () {
    config(['cache.default' => 'array']);
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');
    $ada = tenantUser($acme);
    $grace = tenantUser($globex);
    $acme->execute(fn () => $ada->assignRole(levelRole('Editor', [FakePermission::ReportsView->value])));
    $globex->execute(fn () => $grace->assignRole(levelRole('Editor', [FakePermission::ReportsExport->value])));
    $can = fn (Tenant $tenant, TenantUser $user, FakePermission $permission): bool => $tenant->execute(fn () => (bool) $user->fresh()?->can($permission->value));

    expect($can($acme, $ada, FakePermission::ReportsView))->toBeTrue()
        ->and($can($globex, $grace, FakePermission::ReportsView))->toBeFalse()
        ->and($can($globex, $grace, FakePermission::ReportsExport))->toBeTrue()
        ->and($can($acme, $ada, FakePermission::ReportsExport))->toBeFalse();
});

it('keeps the tenant in the roles unique index, also when teams arrive after tenancy', function () {
    $migrations = dirname(__DIR__, 3).'/database/migrations';

    expect(rolesUniqueColumns())->toBe(['tenant_id', 'name', 'guard_name']);

    (require $migrations.'/tenancy/add_tenant_to_roles_table.php')->up();
    expect(rolesUniqueColumns())->toBe(['tenant_id', 'name', 'guard_name']);

    (require $migrations.'/teams/add_teams_to_permission_tables.php')->up();
    expect(rolesUniqueColumns())->toBe(['tenant_id', 'team_id', 'name', 'guard_name']);
});
