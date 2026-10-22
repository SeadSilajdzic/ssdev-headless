<?php

use Illuminate\Support\Facades\Notification;
use Ssdev\ApiContracts\Testing\InteractsWithApiContract;
use Ssdev\Headless\Features\Teams\Team;
use Ssdev\Headless\Features\Teams\TeamInvitationService;
use Ssdev\Headless\Features\Teams\TeamService;
use Workbench\App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(InteractsWithApiContract::class);

/**
 * @return array{0: Team, 1: User, 2: User}
 */
function contractTeam(): array
{
    $owner = User::factory()->create();
    $team = app(TeamService::class)->create($owner, 'Research');
    $member = User::factory()->create();
    $team->members()->attach($member);
    apiAs($owner);

    return [$team, $owner, $member];
}

beforeEach(function () {
    config(['api-contract.snapshot_dir' => packageSnapshotDirectory(), 'api-contract.strict' => true]);
    Notification::fake();
});

it('GET /api/v1/teams matches contract', function () {
    contractTeam();

    $this->assertMatchesApiContract('GET_api_v1_teams', getJson('/api/v1/teams')->assertOk()->json());
});

it('POST /api/v1/teams matches contract', function () {
    apiAs(User::factory()->create());

    $this->assertMatchesApiContract('POST_api_v1_teams', postJson('/api/v1/teams', ['name' => 'Research'])->assertCreated()->json());
});

it('GET /api/v1/teams/{team} matches contract', function () {
    [$team] = contractTeam();

    $this->assertMatchesApiContract('GET_api_v1_teams_show', getJson("/api/v1/teams/{$team->id}")->assertOk()->json());
});

it('PUT /api/v1/teams/{team} matches contract', function () {
    [$team] = contractTeam();

    $this->assertMatchesApiContract('PUT_api_v1_teams_show', putJson("/api/v1/teams/{$team->id}", ['name' => 'Labs'])->assertOk()->json());
});

it('PUT /api/v1/me/current-team matches contract', function () {
    [$team] = contractTeam();

    $this->assertMatchesApiContract('PUT_api_v1_me_current-team', putJson('/api/v1/me/current-team', ['team_id' => $team->id])->assertOk()->json());
});

it('GET /api/v1/teams/{team}/members matches contract', function () {
    [$team] = contractTeam();

    $this->assertMatchesApiContract('GET_api_v1_teams_show_members', getJson("/api/v1/teams/{$team->id}/members")->assertOk()->json());
});

it('PUT /api/v1/teams/{team}/owner matches contract', function () {
    [$team, , $member] = contractTeam();

    $this->assertMatchesApiContract('PUT_api_v1_teams_show_owner', putJson("/api/v1/teams/{$team->id}/owner", ['user_id' => $member->id])->assertOk()->json());
});

it('POST /api/v1/teams/{team}/invitations matches contract', function () {
    [$team] = contractTeam();

    $response = postJson("/api/v1/teams/{$team->id}/invitations", ['email' => 'ada@example.com'])->assertCreated();

    $this->assertMatchesApiContract('POST_api_v1_teams_show_invitations', $response->json());
});

it('GET /api/v1/teams/{team}/invitations matches contract', function () {
    [$team] = contractTeam();
    postJson("/api/v1/teams/{$team->id}/invitations", ['email' => 'ada@example.com']);

    $this->assertMatchesApiContract('GET_api_v1_teams_show_invitations', getJson("/api/v1/teams/{$team->id}/invitations")->assertOk()->json());
});

it('POST /api/v1/team-invitations/{invitation}/accept matches contract', function () {
    [$team, $owner] = contractTeam();
    $invitation = app(TeamInvitationService::class)->invite($owner, $team, 'ada@example.com', null);
    parse_str((string) parse_url(app(TeamInvitationService::class)->link($invitation), PHP_URL_QUERY), $signature);
    apiAs(User::factory()->create(['email' => 'ada@example.com']));

    $response = postJson("/api/v1/team-invitations/{$invitation->id}/accept?".http_build_query($signature))->assertOk();

    $this->assertMatchesApiContract('POST_api_v1_team-invitations_show_accept', $response->json());
});
