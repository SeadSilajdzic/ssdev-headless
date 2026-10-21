<?php

use Illuminate\Http\Request;
use Ssdev\Headless\Tests\Fixtures\Permissions\FakePermission;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\withToken;

// Guards keep the resolved user between requests in one test; a real request starts fresh.
function startNewRequest(): void
{
    app('auth')->forgetGuards();
}

beforeEach(function () {
    usePermissions(FakePermission::class);
    artisan('headless:permissions:sync');
    apiTestRoute('get', 'reports', fn (Request $request) => $request->user()->can('reports.view') ? 'yes' : 'no', ['auth:sanctum']);
});

it('limits a token to its abilities', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('reports.view');

    withToken($user->createToken('export only', ['reports.export'])->plainTextToken)->getJson('/api/v1/reports')->assertSee('no');
});

it('denies a token ability the owner no longer has', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('reports.view');
    $token = $user->createToken('reports', ['reports.view'])->plainTextToken;

    withToken($token)->getJson('/api/v1/reports')->assertSee('yes');

    $user->revokePermissionTo('reports.view');
    startNewRequest();

    withToken($token)->getJson('/api/v1/reports')->assertSee('no');
});

it('never gives a wildcard token more than its owner', function () {
    $user = User::factory()->create();
    $token = $user->createToken('device', ['*'])->plainTextToken;

    withToken($token)->getJson('/api/v1/reports')->assertSee('no');

    $user->givePermissionTo('reports.view');
    startNewRequest();

    withToken($token)->getJson('/api/v1/reports')->assertSee('yes');
});

it('leaves session users to their permissions', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('reports.view');

    actingAs($user);

    expect($user->can('reports.view'))->toBeTrue();
});
