<?php

use Illuminate\Support\Facades\DB;
use Ssdev\Headless\Features\Teams\Team;
use Ssdev\Headless\Features\Teams\TeamMembershipService;
use Ssdev\Headless\Features\Teams\TeamRepository;
use Ssdev\Headless\Features\Teams\TeamService;
use Workbench\App\Models\User;

/**
 * @return array{0: Team, 1: User, 2: User}
 */
function teamWithMember(): array
{
    $owner = User::factory()->create();
    $team = app(TeamService::class)->create($owner, 'Research');
    $member = User::factory()->create();
    $team->members()->attach($member);

    return [$team, $owner, $member];
}

it('creates a team owned and joined by its creator', function () {
    $owner = User::factory()->create();

    $team = app(TeamService::class)->create($owner, 'Research');

    expect($team->owner->is($owner))->toBeTrue()
        ->and($team->personal)->toBeFalse()
        ->and(app(TeamRepository::class)->isMember($team, $owner))->toBeTrue()
        ->and($owner->fresh()->current_team_id)->toBe($team->id)
        ->and(app(TeamRepository::class)->forMember($owner)->pluck('name')->all())->toBe(['Research']);
});

it('renames a team', function () {
    [$team] = teamWithMember();

    expect(app(TeamService::class)->rename($team, 'Labs')->fresh()->name)->toBe('Labs');
});

it('switches only to a team the user belongs to', function () {
    [$team, $owner] = teamWithMember();
    $second = app(TeamService::class)->create($owner, 'Second');
    $foreign = app(TeamService::class)->create(User::factory()->create(), 'Foreign');

    app(TeamMembershipService::class)->switchTo($owner, $team);
    expect($owner->fresh()->current_team_id)->toBe($team->id);

    expect(fn () => app(TeamMembershipService::class)->switchTo($owner, $foreign))->toThrow(failsOn('team'))
        ->and($owner->fresh()->current_team_id)->toBe($team->id)
        ->and($second->exists)->toBeTrue();
});

it('keeps the owner from leaving', function () {
    [$team, $owner] = teamWithMember();

    expect(fn () => app(TeamMembershipService::class)->leave($owner, $team))->toThrow(failsOn('team'));
});

it('lets a member leave and resets their current team', function () {
    [$team, , $member] = teamWithMember();
    app(TeamMembershipService::class)->switchTo($member, $team);

    app(TeamMembershipService::class)->leave($member, $team);

    expect(app(TeamRepository::class)->isMember($team, $member))->toBeFalse()
        ->and($member->fresh()->current_team_id)->toBeNull();
});

it('removes a member but never the owner', function () {
    [$team, $owner, $member] = teamWithMember();

    app(TeamMembershipService::class)->remove($team, $member);

    expect(app(TeamRepository::class)->isMember($team, $member))->toBeFalse()
        ->and(fn () => app(TeamMembershipService::class)->remove($team, $owner))->toThrow(failsOn('team'));
});

it('transfers ownership to a member only', function () {
    [$team, $owner, $member] = teamWithMember();

    expect(fn () => app(TeamMembershipService::class)->transfer($team, User::factory()->create()))->toThrow(failsOn('team'));

    app(TeamMembershipService::class)->transfer($team, $member);

    expect($team->fresh()->owner->is($member))->toBeTrue()
        ->and(app(TeamRepository::class)->isMember($team, $owner))->toBeTrue();
});

it('deletes a team with its memberships and resets current teams', function () {
    [$team, $owner, $member] = teamWithMember();
    $kept = app(TeamService::class)->create($owner, 'Kept');
    app(TeamMembershipService::class)->switchTo($owner, $team);
    app(TeamMembershipService::class)->switchTo($member, $team);

    app(TeamService::class)->delete($team);

    expect(Team::find($team->id))->toBeNull()
        ->and(DB::table('team_user')->where('team_id', $team->id)->count())->toBe(0)
        ->and($owner->fresh()->current_team_id)->toBe($kept->id)
        ->and($member->fresh()->current_team_id)->toBeNull();
});
