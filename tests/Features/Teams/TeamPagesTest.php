<?php

use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Features\Teams\Notifications\TeamInvitationNotification;
use Ssdev\Headless\Features\Teams\Team;
use Ssdev\Headless\Features\Teams\TeamInvitation;
use Ssdev\Headless\Features\Teams\TeamInvitationService;
use Ssdev\Headless\Features\Teams\TeamRepository;
use Ssdev\Headless\Features\Teams\TeamService;
use Ssdev\Headless\Support\Flash;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: Team, 1: User, 2: User}
 */
function teamPageFixture(): array
{
    $owner = User::factory()->create();
    $team = app(TeamService::class)->create($owner, 'Research');
    $member = User::factory()->create();
    $team->members()->attach($member);

    return [$team, $owner, $member];
}

beforeEach(fn () => Notification::fake());

it('shares the current team and the user\'s teams', function () {
    [$team, $owner] = teamPageFixture();
    app(TeamService::class)->create($owner, 'Sales');
    $owner->forceFill(['current_team_id' => $team->id])->save();

    actingAs($owner->fresh())->get(route('headless.dashboard'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('teams.current.name', 'Research')
        ->where('teams.all', [['id' => $team->id, 'name' => 'Research'], ['id' => $team->id + 1, 'name' => 'Sales']])
        ->where('teams.switchUrl', route('headless.current-team.update'))
        ->where('teams.settingsUrl', route('headless.teams.show', $team->id)));
});

it('creates a team from the page', function () {
    $user = User::factory()->create();

    actingAs($user)->get(route('headless.teams.create'))->assertInertia(fn (AssertableInertia $page) => $page->component(headlessPage('Teams/Create'), false));
    $response = actingAs($user)->post(route('headless.teams.store'), ['name' => 'Research']);

    $team = Team::where('name', 'Research')->firstOrFail();
    $response->assertRedirect(route('headless.teams.show', $team->id))->assertSessionHas(Flash::SUCCESS);
    expect($user->fresh()->current_team_id)->toBe($team->id);
});

it('shows a team to members only', function () {
    [$team, $owner, $member] = teamPageFixture();

    actingAs($member)->get(route('headless.teams.show', $team->id))->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Teams/Show'), false)
        ->where('team.name', 'Research')
        ->where('isOwner', false)
        ->where('members', fn ($members) => collect($members)->where('is_owner', true)->pluck('id')->all() === [$owner->id])
        ->where('invitations', []));
    actingAs(User::factory()->create())->get(route('headless.teams.show', $team->id))->assertNotFound();
});

it('lets only the owner rename, invite, remove members and delete', function () {
    [$team, $owner, $member] = teamPageFixture();

    actingAs($member)->put(route('headless.teams.update', $team->id), ['name' => 'Labs'])->assertForbidden();
    actingAs($member)->post(route('headless.teams.invitations.store', $team->id), ['email' => 'ada@example.com'])->assertForbidden();
    actingAs($member)->delete(route('headless.teams.members.destroy', [$team->id, $owner->id]))->assertForbidden();
    actingAs($member)->delete(route('headless.teams.destroy', $team->id))->assertForbidden();

    actingAs($owner)->put(route('headless.teams.update', $team->id), ['name' => 'Labs'])->assertSessionHas(Flash::SUCCESS);
    actingAs($owner)->post(route('headless.teams.invitations.store', $team->id), ['email' => 'ada@example.com'])->assertSessionHas(Flash::SUCCESS);
    Notification::assertSentOnDemand(TeamInvitationNotification::class);
    actingAs($owner)->delete(route('headless.teams.invitations.destroy', [$team->id, TeamInvitation::sole()->id]))->assertSessionHas(Flash::SUCCESS);
    actingAs($owner)->delete(route('headless.teams.members.destroy', [$team->id, $member->id]))->assertSessionHas(Flash::SUCCESS);
    actingAs($owner)->delete(route('headless.teams.destroy', $team->id))->assertRedirect(route('headless.dashboard'));

    expect(Team::find($team->id))->toBeNull()
        ->and(TeamInvitation::count())->toBe(0);
});

it('switches the current team', function () {
    [$team, $owner] = teamPageFixture();
    $sales = app(TeamService::class)->create($owner, 'Sales');
    $foreign = app(TeamService::class)->create(User::factory()->create(), 'Foreign');

    actingAs($owner)->put(route('headless.current-team.update'), ['team_id' => $team->id])->assertRedirect();
    expect($owner->fresh()->current_team_id)->toBe($team->id);

    actingAs($owner)->put(route('headless.current-team.update'), ['team_id' => $foreign->id])->assertNotFound();
    expect($owner->fresh()->current_team_id)->toBe($team->id)
        ->and($sales->exists)->toBeTrue();
});

it('leaves a team and transfers ownership from the page', function () {
    [$team, $owner, $member] = teamPageFixture();

    actingAs($owner)->delete(route('headless.teams.membership.destroy', $team->id))->assertSessionHasErrors('team');
    actingAs($owner)->put(route('headless.teams.owner.update', $team->id), ['user_id' => $member->id])->assertSessionHas(Flash::SUCCESS);
    expect($team->fresh()->isOwnedBy($member))->toBeTrue();

    actingAs($owner)->delete(route('headless.teams.membership.destroy', $team->id))->assertRedirect(route('headless.dashboard'));
    expect(app(TeamRepository::class)->isMember($team, $owner))->toBeFalse();
});

it('opens the team after accepting an invitation', function () {
    [$team, $owner] = teamPageFixture();
    $ada = User::factory()->create(['email' => 'ada@example.com']);
    $invitation = app(TeamInvitationService::class)->invite($owner, $team, 'ada@example.com', null);

    actingAs($ada)->post(app(TeamInvitationService::class)->link($invitation))->assertRedirect(route('headless.teams.show', $team->id));
});

it('throttles team invitations', function () {
    config(['headless.rate_limits.team_invitations' => 2]);
    [$team, $owner] = teamPageFixture();

    foreach ([1, 2] as $number) {
        actingAs($owner)->post(route('headless.teams.invitations.store', $team->id), ['email' => "user{$number}@example.com"])->assertRedirect();
    }

    actingAs($owner)->post(route('headless.teams.invitations.store', $team->id), ['email' => 'user3@example.com'])->assertTooManyRequests();
});
