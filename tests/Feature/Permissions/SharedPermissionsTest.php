<?php

use Inertia\Testing\AssertableInertia;
use Ssdev\Headless\Tests\Fixtures\Permissions\FakePermission;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;

it('shares the registered permissions the user has', function () {
    usePermissions(FakePermission::class);
    artisan('headless:permissions:sync');
    $user = User::factory()->create();
    $user->givePermissionTo('reports.view');

    actingAs($user)->get(route('headless.dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('auth.permissions', ['reports.view']));
});
