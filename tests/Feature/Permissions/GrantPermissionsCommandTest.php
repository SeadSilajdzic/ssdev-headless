<?php

use Ssdev\Headless\Tests\Fixtures\Permissions\FakePermission;
use Workbench\App\Models\User;

use function Pest\Laravel\artisan;

beforeEach(fn () => usePermissions(FakePermission::class));

it('grants chosen permissions directly to a user', function () {
    $user = User::factory()->create();

    artisan('headless:permissions:grant', ['email' => $user->email, 'permissions' => ['reports.view']])->assertSuccessful();

    expect($user->fresh()->can('reports.view'))->toBeTrue()
        ->and($user->fresh()->can('reports.export'))->toBeFalse();
});

it('grants every registered permission with --all', function () {
    $user = User::factory()->create();

    artisan('headless:permissions:grant', ['email' => $user->email, '--all' => true])->assertSuccessful();

    expect($user->fresh()->can('reports.view'))->toBeTrue()
        ->and($user->fresh()->can('reports.export'))->toBeTrue();
});

it('refuses unknown permissions and grants nothing', function () {
    $user = User::factory()->create();

    artisan('headless:permissions:grant', ['email' => $user->email, 'permissions' => ['reports.view', 'reports.delete']])
        ->expectsOutputToContain('reports.delete')
        ->assertFailed();

    expect($user->fresh()->getAllPermissions())->toBeEmpty();
});

it('refuses an unknown email', function () {
    artisan('headless:permissions:grant', ['email' => 'ghost@example.com', '--all' => true])->assertFailed();
});
