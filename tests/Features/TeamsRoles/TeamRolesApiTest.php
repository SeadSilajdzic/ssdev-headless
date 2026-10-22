<?php

use Ssdev\ApiContracts\Testing\InteractsWithApiContract;
use Ssdev\Headless\Features\Teams\TeamService;
use Workbench\App\Models\User;

use function Pest\Laravel\putJson;

uses(InteractsWithApiContract::class);

beforeEach(fn () => config(['api-contract.snapshot_dir' => packageSnapshotDirectory(), 'api-contract.strict' => true]));

it('sets a member\'s team roles', function () {
    roleManager();
    $owner = apiAs(User::factory()->create());
    $team = app(TeamService::class)->create($owner, 'Research');
    $member = User::factory()->create();
    $team->members()->attach($member);
    $editor = makeRole('Editor', ['reports.view']);
    $admins = makeRole('Admins', ['roles.view']);

    putJson("/api/v1/teams/{$team->id}/members/{$member->id}/roles", ['roles' => [$admins->id]])->assertUnprocessable()->assertJsonValidationErrors('roles');

    $response = putJson("/api/v1/teams/{$team->id}/members/{$member->id}/roles", ['roles' => [$editor->id]])
        ->assertOk()
        ->assertJsonPath('data', [['id' => $editor->id, 'name' => 'Editor']]);

    $this->assertMatchesApiContract('PUT_api_v1_teams_show_members_show_roles', $response->json());
});
