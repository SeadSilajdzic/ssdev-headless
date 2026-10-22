<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;
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
