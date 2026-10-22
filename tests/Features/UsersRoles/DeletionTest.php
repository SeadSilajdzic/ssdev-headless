<?php

use Ssdev\Headless\Features\Roles\RoleAssignmentService;
use Ssdev\Headless\Features\Roles\RoleData;
use Ssdev\Headless\Features\Roles\RoleService;
use Ssdev\Headless\Features\Users\UserService;
use Ssdev\Headless\Permissions\PermissionSynchronizer;
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

it('does not count blocked members as role managers', function () {
    $actor = roleManager();
    $member = User::factory()->create();
    $admins = makeRole('Admins', ['roles.manage'], $member);
    User::factory()->create(['blocked_at' => now()])->assignRole($admins);

    expect(fn () => app(RoleAssignmentService::class)->revoke($actor, $admins, $member))->toThrow(failsOn('role'));
});

it('refuses a role change that leaves nobody able to block users', function () {
    app(PermissionSynchronizer::class)->sync();
    $actor = User::factory()->create();
    $administrator = makeRole('Administrator', ['roles.view', 'roles.manage', 'users.view', 'users.block'], $actor);
    makeRole('Spare managers', ['roles.manage'], User::factory()->create());

    expect(fn () => app(RoleService::class)->update($actor, $administrator, new RoleData('Administrator', ['roles.view', 'roles.manage', 'users.view'])))->toThrow(failsOn('role'))
        ->and(fn () => app(RoleAssignmentService::class)->revoke($actor, $administrator, $actor))->toThrow(failsOn('role'))
        ->and(fn () => app(RoleService::class)->delete($actor, $administrator))->toThrow(failsOn('role'));
});
