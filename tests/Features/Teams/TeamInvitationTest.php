<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Features\Teams\Notifications\TeamInvitationNotification;
use Ssdev\Headless\Features\Teams\Team;
use Ssdev\Headless\Features\Teams\TeamInvitation;
use Ssdev\Headless\Features\Teams\TeamInvitationService;
use Ssdev\Headless\Features\Teams\TeamRepository;
use Ssdev\Headless\Features\Teams\TeamService;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\travel;

/**
 * @return array{0: Team, 1: User}
 */
function invitingTeam(): array
{
    $owner = User::factory()->create();

    return [app(TeamService::class)->create($owner, 'Research'), $owner];
}

/**
 * The link in the last invitation mailed to the address.
 */
function teamInvitationLink(string $email): string
{
    $link = '';

    Notification::assertSentOnDemand(TeamInvitationNotification::class, function (TeamInvitationNotification $notification, array $channels, object $notifiable) use ($email, &$link) {
        if ($notifiable->routes['mail'] !== $email) {
            return false;
        }

        $link = $notification->url;

        return true;
    });

    return $link;
}

beforeEach(fn () => Notification::fake());

it('invites by email and adds the member on accept', function () {
    [$team, $owner] = invitingTeam();
    $ada = User::factory()->create(['email' => 'ada@example.com']);

    app(TeamInvitationService::class)->invite($owner, $team, ' Ada@Example.com ', null);
    $link = teamInvitationLink('ada@example.com');

    actingAs($ada)->get($link)->assertInertia(fn (AssertableInertia $page) => $page
        ->component(headlessPage('Teams/Invitation'), false)
        ->where('team.name', 'Research')
        ->where('acceptUrl', $link));
    actingAs($ada)->post($link)->assertRedirect();

    expect(app(TeamRepository::class)->isMember($team, $ada))->toBeTrue()
        ->and($ada->fresh()->current_team_id)->toBe($team->id)
        ->and(TeamInvitation::count())->toBe(0);
});

it('refuses a link for another email', function () {
    [$team, $owner] = invitingTeam();
    $grace = User::factory()->create(['email' => 'grace@example.com']);

    app(TeamInvitationService::class)->invite($owner, $team, 'ada@example.com', null);

    actingAs($grace)->post(teamInvitationLink('ada@example.com'))->assertSessionHasErrors('invitation');

    expect(app(TeamRepository::class)->isMember($team, $grace))->toBeFalse();
});

it('refuses a changed or expired signature', function () {
    [$team, $owner] = invitingTeam();
    $ada = User::factory()->create(['email' => 'ada@example.com']);
    app(TeamInvitationService::class)->invite($owner, $team, 'ada@example.com', null);
    $link = teamInvitationLink('ada@example.com');

    actingAs($ada)->post($link.'x')->assertForbidden();

    travel(10081)->minutes();

    actingAs($ada)->post($link)->assertForbidden();
});

it('accepts once', function () {
    [$team, $owner] = invitingTeam();
    $ada = User::factory()->create(['email' => 'ada@example.com']);
    app(TeamInvitationService::class)->invite($owner, $team, 'ada@example.com', null);
    $link = teamInvitationLink('ada@example.com');

    actingAs($ada)->post($link)->assertRedirect();
    actingAs($ada)->post($link)->assertNotFound();

    expect($team->members()->count())->toBe(2);
});

it('sends guests to sign in first', function () {
    [$team, $owner] = invitingTeam();
    app(TeamInvitationService::class)->invite($owner, $team, 'ada@example.com', null);

    get(teamInvitationLink('ada@example.com'))->assertRedirect(route('login'));
});

it('sends the same invitation again instead of a duplicate', function () {
    [$team, $owner] = invitingTeam();

    app(TeamInvitationService::class)->invite($owner, $team, 'ada@example.com', null);
    app(TeamInvitationService::class)->invite($owner, $team, 'ADA@example.com', null);

    expect(TeamInvitation::count())->toBe(1);
    Notification::assertSentOnDemandTimes(TeamInvitationNotification::class, 2);
});

it('refuses inviting a member', function () {
    [$team, $owner] = invitingTeam();

    expect(fn () => app(TeamInvitationService::class)->invite($owner, $team, $owner->email, null))->toThrow(failsOn('email'));
});

it('cancels only the team\'s own invitations', function () {
    [$team, $owner] = invitingTeam();
    [$other, $otherOwner] = invitingTeam();
    $invitation = app(TeamInvitationService::class)->invite($otherOwner, $other, 'ada@example.com', null);

    expect(fn () => app(TeamInvitationService::class)->cancel($team, $invitation))->toThrow(ModelNotFoundException::class);

    app(TeamInvitationService::class)->cancel($other, $invitation);

    expect(TeamInvitation::count())->toBe(0);
});
