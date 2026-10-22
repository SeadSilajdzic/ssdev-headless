<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Features\Roles\RoleAssignmentService;
use Ssdev\Headless\Features\Roles\RoleRepository;
use Ssdev\Headless\Features\Teams\Events\TeamMemberRolesUpdated;
use Ssdev\Headless\Features\Teams\Team;
use Ssdev\Headless\Features\Teams\TeamRoleService;
use Ssdev\Headless\Features\Teams\TeamService;
use Workbench\App\Models\User;

/**
 * @return array{0: Team, 1: User, 2: User}
 */
function ownedTeamWithMember(): array
{
    roleManager();
    $owner = User::factory()->create();
    $team = app(TeamService::class)->create($owner, 'Research');
    $member = User::factory()->create();
    $team->members()->attach($member);

    return [$team, $owner, $member];
}

/**
 * @return list<string>
 */
function teamRoleNames(Team $team, User $member): array
{
    return app(TeamRoleService::class)->rolesOf($team, $member)->pluck('name')->all();
}

it('lets the owner give members team roles', function () {
    [$team, $owner, $member] = ownedTeamWithMember();
    $editor = makeRole('Editor', ['reports.view']);
    $viewer = makeRole('Viewer', ['reports.view']);

    app(TeamRoleService::class)->sync($owner, $team, $member, [$editor->id]);
    expect(teamRoleNames($team, $member))->toBe(['Editor']);

    app(TeamRoleService::class)->sync($owner, $team, $member, [$viewer->id]);
    expect(teamRoleNames($team, $member))->toBe(['Viewer']);
});

it('leaves the global roles of a member alone', function () {
    [$team, $owner, $member] = ownedTeamWithMember();
    $global = makeRole('Staff', ['reports.view']);
    app(RoleAssignmentService::class)->assignWithoutActor($global, $member);

    app(TeamRoleService::class)->sync($owner, $team, $member, []);

    expect(app(RoleRepository::class)->assignedTo($member)->pluck('name')->all())->toBe(['Staff']);
});

it('refuses a role with a system permission', function () {
    [$team, $owner, $member] = ownedTeamWithMember();
    $admins = makeRole('Admins', ['roles.view', 'reports.view']);

    expect(fn () => app(TeamRoleService::class)->sync($owner, $team, $member, [$admins->id]))->toThrow(failsOn('roles'))
        ->and(app(RoleRepository::class)->assignableInTeams()->pluck('name')->all())->not->toContain('Admins');
});

it('refuses a role that belongs to the global context only', function () {
    [$team, $owner, $member] = ownedTeamWithMember();
    // As Spatie's Role::create leaves it in the global context; Spatie never counts such a role inside a team.
    $seeded = Role::query()->forceCreate(['name' => 'Seeded', 'guard_name' => 'web', 'team_id' => 0]);

    expect(fn () => app(TeamRoleService::class)->sync($owner, $team, $member, [$seeded->id]))->toThrow(failsOn('roles'))
        ->and(app(RoleRepository::class)->assignableInTeams()->pluck('name')->all())->not->toContain('Seeded');
});

it('treats a team role added by a parallel request as already given', function () {
    [$team, $owner, $member] = ownedTeamWithMember();
    $editor = makeRole('Editor', ['reports.view']);
    $raced = false;

    // Gives the role right after the check for an existing assignment, as a parallel request would.
    DB::listen(function (QueryExecuted $query) use (&$raced, $editor, $member, $team) {
        if ($raced || ! str_starts_with($query->sql, 'select exists') || ! str_contains($query->sql, 'model_has_roles')) {
            return;
        }

        $raced = true;
        $editor->users()->attach($member->id, ['team_id' => $team->id]);
    });

    app(TeamRoleService::class)->sync($owner, $team, $member, [$editor->id]);

    expect($raced)->toBeTrue()
        ->and(teamRoleNames($team, $member))->toBe(['Editor']);
});

it('refuses members of other teams', function () {
    [$team, $owner] = ownedTeamWithMember();

    expect(fn () => app(TeamRoleService::class)->sync($owner, $team, User::factory()->create(), []))->toThrow(failsOn('team'));
});

it('forbids non-owners', function () {
    [$team, , $member] = ownedTeamWithMember();

    expect(fn () => app(TeamRoleService::class)->sync($member, $team, $member, []))->toThrow(AuthorizationException::class);
});

it('announces the change', function () {
    Event::fake([TeamMemberRolesUpdated::class]);
    [$team, $owner, $member] = ownedTeamWithMember();
    $editor = makeRole('Editor', ['reports.view']);

    app(TeamRoleService::class)->sync($owner, $team, $member, [$editor->id]);

    Event::assertDispatched(TeamMemberRolesUpdated::class, fn (TeamMemberRolesUpdated $event) => $event->team->is($team)
        && $event->member->is($member)
        && $event->actor->is($owner)
        && $event->added === [$editor->id]
        && $event->removed === []);
});
