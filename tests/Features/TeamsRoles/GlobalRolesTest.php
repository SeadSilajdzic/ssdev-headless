<?php

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Account\AdminAccountCreator;
use Ssdev\Headless\Features\Roles\RoleAssignmentService;
use Ssdev\Headless\Features\Roles\RoleRepository;
use Ssdev\Headless\Features\Roles\RolesModule;
use Ssdev\Headless\Features\Teams\Team;
use Ssdev\Headless\Features\Teams\TeamContext;
use Ssdev\Headless\Features\Teams\TeamService;
use Ssdev\Headless\Features\Users\Criteria\FilterUsersByRole;
use Ssdev\Headless\Features\Users\ManagedUserRepository;
use Workbench\App\Models\User;

function assignInTeam(Role $role, User $user, Team $team): void
{
    $user->roles()->attach($role->getKey(), ['team_id' => $team->getKey()]);
}

it('lists only global members on the roles page', function () {
    roleManager();
    $role = makeRole('Editor', []);
    $global = User::factory()->create();
    app(RoleAssignmentService::class)->assignWithoutActor($role, $global);
    $teamMember = User::factory()->create();
    assignInTeam($role, $teamMember, app(TeamService::class)->create($teamMember, 'Research'));

    expect(app(RoleRepository::class)->members($role)->pluck('id')->all())->toBe([$global->id])
        ->and(app(RoleRepository::class)->findOrFail($role->getKey())->users_count)->toBe(1)
        ->and(app(RoleRepository::class)->assignedTo($teamMember))->toBeEmpty();
});

it('ignores team assignments in the last manager rule', function () {
    $actor = roleManager();
    $manager = User::factory()->create();
    $managers = makeRole('Managers', ['roles.manage']);
    app(RoleAssignmentService::class)->assignWithoutActor($managers, $manager);
    $teamMember = User::factory()->create();
    assignInTeam($managers, $teamMember, app(TeamService::class)->create($teamMember, 'Research'));

    expect(fn () => app(RoleAssignmentService::class)->revoke($actor, $managers, $manager))->toThrow(failsOn('role'));
});

it('gives the first admin a global role', function () {
    $admin = app(AdminAccountCreator::class)->create('Ada Lovelace', 'ada@example.com', 'S3cure-password!');

    expect(DB::table('model_has_roles')->where('model_id', $admin->id)->value('team_id'))->toEqual(TeamContext::GLOBAL)
        ->and(app(RoleRepository::class)->assignedTo($admin)->pluck('name')->all())->toBe([RolesModule::ADMIN_ROLE]);
});

it('filters users by global roles only', function () {
    roleManager();
    $role = makeRole('Editor', []);
    $global = User::factory()->create(['email' => 'global@example.com']);
    app(RoleAssignmentService::class)->assignWithoutActor($role, $global);
    $teamMember = User::factory()->create(['email' => 'team@example.com']);
    assignInTeam($role, $teamMember, app(TeamService::class)->create($teamMember, 'Research'));

    $emails = app(ManagedUserRepository::class)->paginate([new FilterUsersByRole($role->getKey())], 50)->getCollection()->pluck('email')->all();

    expect($emails)->toBe(['global@example.com']);
});
