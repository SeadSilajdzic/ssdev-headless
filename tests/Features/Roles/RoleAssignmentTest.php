<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Ssdev\Headless\Features\Roles\Events\RoleAssigned;
use Ssdev\Headless\Features\Roles\Events\RoleRevoked;
use Ssdev\Headless\Features\Roles\RoleAssignmentService;
use Workbench\App\Models\User;

it('assigns a role and announces it', function () {
    Event::fake([RoleAssigned::class]);
    $actor = roleManager(['reports.view']);
    $role = makeRole('Viewer', ['reports.view']);
    $user = User::factory()->create();

    app(RoleAssignmentService::class)->assign($actor, $role, $user);

    expect($user->fresh()->hasRole('Viewer'))->toBeTrue();
    Event::assertDispatched(RoleAssigned::class, fn (RoleAssigned $event) => $event->role->is($role) && $event->user->is($user) && $event->actor->is($actor));
});

it('refuses to assign a role with permissions the actor lacks', function () {
    $actor = roleManager(['reports.view']);
    $role = makeRole('Exporter', ['reports.export']);

    expect(fn () => app(RoleAssignmentService::class)->assign($actor, $role, User::factory()->create()))->toThrow(failsOn('role'));
});

it('revokes a role and announces it', function () {
    Event::fake([RoleRevoked::class]);
    $actor = roleManager(['reports.view']);
    $user = User::factory()->create();
    $role = makeRole('Viewer', ['reports.view'], $user);

    app(RoleAssignmentService::class)->revoke($actor, $role, $user);

    expect($user->fresh()->hasRole('Viewer'))->toBeFalse();
    Event::assertDispatched(RoleRevoked::class);
});

it('keeps the last member of the last managing role', function () {
    $actor = roleManager();
    $member = User::factory()->create();
    $admin = makeRole('Administrator', ['roles.manage'], $member);

    expect(fn () => app(RoleAssignmentService::class)->revoke($actor, $admin, $member))->toThrow(failsOn('role'));
});

it('assigns the same role twice without duplicates', function () {
    $actor = roleManager(['reports.view']);
    $role = makeRole('Viewer', ['reports.view']);
    $user = User::factory()->create();

    app(RoleAssignmentService::class)->assign($actor, $role, $user);
    app(RoleAssignmentService::class)->assign($actor, $role, $user);

    expect(DB::table('model_has_roles')->where('role_id', $role->getKey())->count())->toBe(1);
});
