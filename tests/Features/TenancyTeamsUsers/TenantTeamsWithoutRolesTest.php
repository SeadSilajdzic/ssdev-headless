<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Ssdev\Headless\Features\Teams\TeamContext;
use Ssdev\Headless\Features\Teams\TeamService;
use Ssdev\Headless\Features\Tenancy\TenantDeletionService;
use Ssdev\Headless\Features\Tenancy\TenantOwnerService;
use Ssdev\Headless\Features\Tenancy\TenantService;
use Ssdev\Headless\Features\Users\UserPermission;
use Ssdev\Headless\Features\Users\UserService;
use Ssdev\Headless\Permissions\PermissionSynchronizer;
use Workbench\App\Models\TenantUser;

use function Pest\Laravel\actingAs;

beforeEach(fn () => app(PermissionSynchronizer::class)->sync());

it('gives the owner the tenant\'s permissions directly and globally, not in the creating admin\'s team', function () {
    Notification::fake();
    $admin = TenantUser::factory()->create();
    app(TeamService::class)->create($admin, 'Platform');
    actingAs($admin->fresh());

    $tenant = app(TenantService::class)->create($admin, 'Acme', 'acme', 'owner@acme.test');
    $owner = app(TenantOwnerService::class)->owner($tenant) ?? throw new LogicException('No owner.');

    expect(DB::table('model_has_permissions')->where('model_id', $owner->getKey())->distinct()->pluck('team_id')->all())->toBe([TeamContext::GLOBAL])
        ->and(DB::table('model_has_roles')->where('model_id', $owner->getKey())->exists())->toBeFalse()
        ->and($tenant->execute(function () use ($owner): bool {
            actingAs($owner);

            return $owner->can(UserPermission::Delete->value);
        }))->toBeTrue();
});

it('counts only the tenant\'s global holders as user managers', function () {
    $acme = makeTenant('acme');
    $actor = tenantUser($acme);
    $manager = tenantUser($acme);
    $teamMember = tenantUser($acme);
    $acme->execute(function () use ($actor, $manager, $teamMember): void {
        $actor->givePermissionTo(UserPermission::Delete->value);
        $manager->givePermissionTo(UserPermission::Block->value);
        $team = app(TeamService::class)->create($teamMember, 'Research');
        $teamMember->permissions()->attach(DB::table('permissions')->where('name', UserPermission::Block->value)->value('id'), ['team_id' => $team->getKey()]);
    });
    TenantUser::factory()->create()->givePermissionTo(UserPermission::Block->value);
    $globex = makeTenant('globex');
    $globexManager = tenantUser($globex);
    $globex->execute(fn () => $globexManager->givePermissionTo(UserPermission::Block->value));

    expect(fn () => $acme->execute(fn () => app(UserService::class)->delete($actor, $manager)))->toThrow(failsOn('user'));
});

it('deletes the tenant\'s teams with the tenant', function () {
    $acme = makeTenant('acme');
    $central = app(TeamService::class)->create(TenantUser::factory()->create(), 'Platform');
    $acmeOwner = tenantUser($acme);
    $acme->execute(fn () => app(TeamService::class)->create($acmeOwner, 'Acme team'));

    app(TenantDeletionService::class)->delete(TenantUser::factory()->create(), $acme);

    expect(DB::table('teams')->pluck('id')->all())->toBe([$central->id]);
});
