<?php

use Ssdev\Headless\Features\Teams\Team;
use Ssdev\Headless\Features\Teams\TeamService;
use Ssdev\Headless\Features\Users\UserService;
use Workbench\App\Models\User;

it('refuses an admin deleting an owner whose team has other members', function () {
    $admin = permittedUser('users.delete');
    $owner = User::factory()->create();
    $team = app(TeamService::class)->create($owner, 'Research');
    $team->members()->attach(User::factory()->create());

    expect(fn () => app(UserService::class)->delete($admin, $owner))->toThrow(failsOn('account'))
        ->and($owner->fresh()->trashed())->toBeFalse();
});

it('keeps the solo teams of a softly deleted user', function () {
    $admin = permittedUser('users.delete');
    $owner = User::factory()->create();
    app(TeamService::class)->create($owner, 'Solo');

    app(UserService::class)->delete($admin, $owner);

    expect($owner->fresh()->trashed())->toBeTrue()
        ->and(Team::count())->toBe(1);
});
