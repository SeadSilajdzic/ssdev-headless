<?php

use Inertia\Testing\AssertableInertia;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

it('filters users by role and offers the roles as options', function () {
    $viewer = permittedUser('users.view', 'roles.view');
    $role = makeRole('Editor', []);
    User::factory()->create(['email' => 'editor@example.com'])->assignRole($role);
    User::factory()->create(['email' => 'reader@example.com']);

    actingAs($viewer)->get(route('headless.users.index', ['role' => $role->id]))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('users.meta.total', 1)
        ->where('users.data.0.email', 'editor@example.com')
        ->where('roles', [['id' => $role->id, 'name' => 'Editor']]));
});
