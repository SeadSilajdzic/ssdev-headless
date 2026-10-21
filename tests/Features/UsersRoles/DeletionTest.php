<?php

use Ssdev\Headless\Features\Users\UserService;
use Workbench\App\Models\User;

it('refuses to delete the last member of the last managing role', function () {
    $actor = permittedUser('users.delete');
    $member = User::factory()->create();
    makeRole('Administrator', ['roles.manage'], $member);

    expect(fn () => app(UserService::class)->delete($actor, $member))->toThrow(failsOn('role'))
        ->and($member->fresh()->trashed())->toBeFalse();
});

it('deletes a member of a managing role that keeps another member', function () {
    $actor = permittedUser('users.delete');
    $member = User::factory()->create();
    $role = makeRole('Administrator', ['roles.manage'], $member);
    User::factory()->create()->assignRole($role);

    app(UserService::class)->delete($actor, $member);

    expect($member->fresh()->trashed())->toBeTrue();
});
