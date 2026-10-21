<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Ssdev\Headless\Api\ApiRoutes;
use Ssdev\Headless\Features\FeatureModule;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Installation\InstallContext;
use Ssdev\Headless\Installation\ProjectFiles;
use Ssdev\Headless\Permissions\HeadlessPermission;
use Ssdev\Headless\Permissions\PermissionRegistry;
use Ssdev\Headless\Tests\Support\FixtureProject;
use Ssdev\Headless\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)
    ->afterEach(fn () => FixtureProject::cleanup())
    ->in('Feature', 'Unit');

function headlessPage(string $name): string
{
    expect(file_exists(__DIR__."/../resources/js/pages/{$name}.vue"))->toBeTrue("Missing page resources/js/pages/{$name}.vue");

    return 'Headless/'.$name;
}

function fixtureProject(): ProjectFiles
{
    $files = FixtureProject::create();
    app()->instance(ProjectFiles::class, $files);

    return $files;
}

/**
 * @param  list<FeatureModule>  $features
 */
function installContext(ProjectFiles $files, array $features = [], bool $overwrite = false): InstallContext
{
    return new InstallContext($files, $features, fn (string $path) => $overwrite);
}

/**
 * @param  list<FeatureModule>  $available
 * @param  list<string>  $enabled
 */
function useFeatures(array $available, array $enabled = []): void
{
    app()->instance(FeatureRegistry::class, new FeatureRegistry($available, $enabled));
}

function useDatabaseSessions(): void
{
    config(['session.driver' => 'database']);
    app('session')->forgetDrivers();
    app()->forgetInstance('session.store');
}

function expectNoSensitiveProps(TestResponse $response, Model $user): void
{
    $props = $response->viewData('page')['props'];
    expect(json_encode($props, JSON_THROW_ON_ERROR))->not->toContain(json_encode($user->getAuthPassword(), JSON_THROW_ON_ERROR));

    // Translation keys such as "auth.fields.password" are labels, not data.
    unset($props['headless']['translations']);
    $data = json_encode($props, JSON_THROW_ON_ERROR);

    foreach (['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'] as $key) {
        expect($data)->not->toContain("\"{$key}\"");
    }
}

/**
 * @param  class-string<HeadlessPermission>  ...$enums
 */
function usePermissions(string ...$enums): void
{
    foreach ($enums as $enum) {
        app(PermissionRegistry::class)->register($enum);
    }
}

/**
 * Registers a route inside the package API group, with the same middleware as the package routes.
 *
 * @param  list<string>  $middleware
 */
function apiTestRoute(string $method, string $uri, Closure $action, array $middleware = []): void
{
    Route::prefix((string) config('headless.api.prefix'))
        ->middleware([...ApiRoutes::MIDDLEWARE, ...$middleware])
        ->{$method}($uri, $action);
}

/**
 * @param  list<string>  $abilities
 */
function apiAs(Authenticatable $user, array $abilities = ['*']): Authenticatable
{
    Sanctum::actingAs($user, $abilities);

    return $user;
}
