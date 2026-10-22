<?php

use Illuminate\Support\Facades\Notification;
use Ssdev\Headless\Features\Teams\Team;
use Ssdev\Headless\Features\Teams\TeamInvitation;
use Ssdev\Headless\Features\Teams\TeamInvitationService;
use Ssdev\Headless\Features\Teams\TeamRepository;
use Ssdev\Headless\Features\Teams\TeamService;
use Workbench\App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(fn () => Notification::fake());

it('lists only my teams', function () {
    $user = apiAs(User::factory()->create());
    app(TeamService::class)->create($user, 'Research');
    app(TeamService::class)->create(User::factory()->create(), 'Foreign');

    getJson('/api/v1/teams')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Research');
});

it('answers 404 for another team', function () {
    apiAs(User::factory()->create());
    $foreign = app(TeamService::class)->create(User::factory()->create(), 'Foreign');

    getJson("/api/v1/teams/{$foreign->id}")->assertNotFound()->assertJsonStructure(['message']);
    putJson('/api/v1/me/current-team', ['team_id' => $foreign->id])->assertNotFound();
});

it('creates, renames, switches and deletes a team', function () {
    $user = apiAs(User::factory()->create());

    $id = postJson('/api/v1/teams', ['name' => 'Research'])->assertCreated()->assertJsonPath('data.is_owner', true)->json('data.id');
    putJson("/api/v1/teams/{$id}", ['name' => 'Labs'])->assertOk()->assertJsonPath('data.name', 'Labs');
    $other = app(TeamService::class)->create($user, 'Other');
    putJson('/api/v1/me/current-team', ['team_id' => $id])->assertOk()->assertJsonPath('data.is_current', true);
    deleteJson("/api/v1/teams/{$id}")->assertNoContent();

    expect(Team::find($id))->toBeNull()
        ->and($other->exists)->toBeTrue();
});

it('manages members and invitations as the owner only', function () {
    $owner = User::factory()->create();
    $team = app(TeamService::class)->create($owner, 'Research');
    $member = User::factory()->create();
    $team->members()->attach($member);

    apiAs($member);
    getJson("/api/v1/teams/{$team->id}/members")->assertOk()->assertJsonCount(2, 'data');
    postJson("/api/v1/teams/{$team->id}/invitations", ['email' => 'ada@example.com'])->assertForbidden();
    deleteJson("/api/v1/teams/{$team->id}/membership")->assertNoContent();
    expect(app(TeamRepository::class)->isMember($team, $member))->toBeFalse();

    $team->members()->attach($member);
    apiAs($owner);
    postJson("/api/v1/teams/{$team->id}/invitations", ['email' => 'ada@example.com'])->assertCreated()->assertJsonPath('data.email', 'ada@example.com');
    getJson("/api/v1/teams/{$team->id}/invitations")->assertOk()->assertJsonCount(1, 'data');
    deleteJson("/api/v1/teams/{$team->id}/invitations/".TeamInvitation::sole()->id)->assertNoContent();
    putJson("/api/v1/teams/{$team->id}/owner", ['user_id' => $member->id])->assertOk()->assertJsonPath('data.is_owner', false);
    deleteJson("/api/v1/teams/{$team->id}/members/{$owner->id}")->assertForbidden();
});

it('accepts a signed invitation', function () {
    $owner = User::factory()->create();
    $team = app(TeamService::class)->create($owner, 'Research');
    $invitation = app(TeamInvitationService::class)->invite($owner, $team, 'ada@example.com', null);
    $link = app(TeamInvitationService::class)->link($invitation);
    parse_str((string) parse_url($link, PHP_URL_QUERY), $signature);
    $ada = apiAs(User::factory()->create(['email' => 'ada@example.com']));

    postJson("/api/v1/team-invitations/{$invitation->id}/accept?signature=forged&expires={$signature['expires']}")->assertForbidden();
    postJson("/api/v1/team-invitations/{$invitation->id}/accept?".http_build_query($signature))->assertOk()->assertJsonPath('data.name', 'Research');

    expect(app(TeamRepository::class)->isMember($team, $ada))->toBeTrue();
});
