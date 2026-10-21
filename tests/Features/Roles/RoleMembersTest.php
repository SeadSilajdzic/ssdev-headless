<?php

use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Support\Flash;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

it('lists the members on the role page', function () {
    $actor = roleManager(['reports.view']);
    $member = User::factory()->create(['name' => 'Grace Hopper']);
    $role = makeRole('Viewer', ['reports.view'], $member);

    actingAs($actor)->get(route('headless.roles.edit', $role->getKey()))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('members.data.0.email', $member->email)
        ->where('members.data.0.name', 'Grace Hopper')
        ->where('membersUrl', route('headless.roles.members.store', $role->getKey())));
});

it('adds a member by email', function () {
    $actor = roleManager(['reports.view']);
    $role = makeRole('Viewer', ['reports.view']);
    $user = User::factory()->create();

    actingAs($actor)->post(route('headless.roles.members.store', $role->getKey()), ['email' => strtoupper($user->email)])
        ->assertRedirect()
        ->assertSessionHas(Flash::SUCCESS);

    expect($user->fresh()->hasRole('Viewer'))->toBeTrue();
});

it('rejects an unknown email', function () {
    $actor = roleManager(['reports.view']);
    $role = makeRole('Viewer', ['reports.view']);

    actingAs($actor)->post(route('headless.roles.members.store', $role->getKey()), ['email' => 'ghost@example.com'])
        ->assertSessionHasErrors(['email' => __('headless::roles.errors.unknown_user')]);
});

it('removes a member', function () {
    $actor = roleManager(['reports.view']);
    $member = User::factory()->create();
    $role = makeRole('Viewer', ['reports.view'], $member);

    actingAs($actor)->delete(route('headless.roles.members.destroy', [$role->getKey(), $member->getKey()]))->assertSessionHas(Flash::SUCCESS);

    expect($member->fresh()->hasRole('Viewer'))->toBeFalse();
});

it('keeps the last member of the last managing role', function () {
    $actor = roleManager();
    $member = User::factory()->create();
    $admin = makeRole('Administrator', ['roles.manage'], $member);

    actingAs($actor)->delete(route('headless.roles.members.destroy', [$admin->getKey(), $member->getKey()]))->assertSessionHasErrors('role');

    expect($member->fresh()->hasRole('Administrator'))->toBeTrue();
});

it('answers not found for a user who is not a member', function () {
    $actor = roleManager(['reports.view']);
    $role = makeRole('Viewer', ['reports.view']);

    actingAs($actor)->delete(route('headless.roles.members.destroy', [$role->getKey(), User::factory()->create()->getKey()]))->assertNotFound();
});

it('forbids member changes with roles.view only', function () {
    $viewer = roleManager();
    $viewer->revokePermissionTo('roles.manage');
    $role = makeRole('Viewer', []);

    actingAs($viewer)->post(route('headless.roles.members.store', $role->getKey()), ['email' => $viewer->email])->assertForbidden();
});
