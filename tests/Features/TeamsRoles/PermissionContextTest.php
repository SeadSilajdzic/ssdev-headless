<?php

use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Features\Teams\Team;
use Ssdev\Headless\Features\Teams\TeamService;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

/**
 * The user as a new request would load it, signed in with the given current team.
 */
function inTeam(User $user, ?Team $team): User
{
    $user->forceFill(['current_team_id' => $team?->getKey()])->save();
    $fresh = $user->fresh();
    actingAs($fresh);

    return $fresh;
}

/**
 * @param  list<string>  $permissions
 */
function giveTeamRole(User $user, Team $team, string $name, array $permissions): void
{
    $role = makeRole($name, $permissions);
    $user->roles()->attach($role->getKey(), ['team_id' => $team->getKey()]);
}

it('keeps global permissions in every team', function () {
    $admin = roleManager();
    $team = app(TeamService::class)->create($admin, 'Research');

    expect(inTeam($admin, $team)->can('roles.manage'))->toBeTrue()
        ->and(inTeam($admin, null)->can('roles.manage'))->toBeTrue();
});

it('grants a team role only in its team', function () {
    roleManager();
    $user = User::factory()->create();
    $research = app(TeamService::class)->create($user, 'Research');
    $sales = app(TeamService::class)->create($user, 'Sales');
    giveTeamRole($user, $research, 'Researcher', ['reports.view']);

    expect(inTeam($user, $research)->can('reports.view'))->toBeTrue()
        ->and(inTeam($user, $sales)->can('reports.view'))->toBeFalse()
        ->and(inTeam($user, null)->can('reports.view'))->toBeFalse();
});

it('never counts a system permission given inside a team', function () {
    roleManager();
    $user = User::factory()->create();
    $team = app(TeamService::class)->create($user, 'Research');
    giveTeamRole($user, $team, 'Sneaky', ['roles.manage']);

    expect(inTeam($user, $team)->can('roles.manage'))->toBeFalse();
});

it('limits tokens to their abilities in a team as well', function () {
    roleManager();
    $user = User::factory()->create();
    $team = app(TeamService::class)->create($user, 'Research');
    giveTeamRole($user, $team, 'Reporter', ['reports.view', 'reports.export']);
    $user = inTeam($user, $team);

    apiAs($user, ['reports.view']);

    expect($user->can('reports.view'))->toBeTrue()
        ->and($user->can('reports.export'))->toBeFalse();
});

it('shares the permissions of the current team with the frontend', function () {
    $admin = roleManager();
    $team = app(TeamService::class)->create($admin, 'Research');
    giveTeamRole($admin, $team, 'Researcher', ['reports.view']);

    inTeam($admin, $team);

    $this->get(route('headless.dashboard'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('auth.permissions', fn ($permissions) => collect($permissions)->contains('reports.view') && collect($permissions)->contains('roles.manage')));
});
