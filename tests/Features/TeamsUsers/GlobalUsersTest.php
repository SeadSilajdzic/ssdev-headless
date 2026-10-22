<?php

use Ssdev\Headless\Features\Teams\TeamService;
use Ssdev\Headless\Features\Users\UserService;
use Workbench\App\Models\User;

it('counts only global holders as user managers', function () {
    $actor = permittedUser('users.delete');
    $manager = permittedUser('users.block');
    $teamMember = User::factory()->create();
    $team = app(TeamService::class)->create($teamMember, 'Research');
    $teamMember->roles()->attach(makeRole('Team blockers', ['users.block'])->getKey(), ['team_id' => $team->getKey()]);

    expect(fn () => app(UserService::class)->delete($actor, $manager))->toThrow(failsOn('user'));
});
