<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Laravel\Fortify\Fortify;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Api\ApiRoutes;
use Ssdev\Headless\Features\FeatureModule;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Features\TwoFactor\TwoFactorService;
use Ssdev\Headless\Installation\InstallContext;
use Ssdev\Headless\Installation\ProjectFiles;
use Ssdev\Headless\Permissions\HeadlessPermission;
use Ssdev\Headless\Permissions\PermissionRegistry;
use Ssdev\Headless\Permissions\PermissionSynchronizer;
use Ssdev\Headless\Tests\Fixtures\Permissions\FakePermission;
use Ssdev\Headless\Tests\Support\FixtureProject;
use Ssdev\Headless\Tests\Support\RolesTestCase;
use Ssdev\Headless\Tests\Support\TeamsRolesTestCase;
use Ssdev\Headless\Tests\Support\TeamsTestCase;
use Ssdev\Headless\Tests\Support\TeamsUsersTestCase;
use Ssdev\Headless\Tests\Support\TwoFactorTestCase;
use Ssdev\Headless\Tests\Support\UsersRolesTestCase;
use Ssdev\Headless\Tests\Support\UsersTestCase;
use Ssdev\Headless\Tests\TestCase;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\withSession;

uses(TestCase::class, RefreshDatabase::class)
    ->afterEach(fn () => FixtureProject::cleanup())
    ->in('Feature', 'Unit');

uses(TwoFactorTestCase::class, RefreshDatabase::class)->in('Features/TwoFactor');

uses(RolesTestCase::class, RefreshDatabase::class)->in('Features/Roles');

uses(UsersTestCase::class, RefreshDatabase::class)->in('Features/Users');

uses(UsersRolesTestCase::class, RefreshDatabase::class)->in('Features/UsersRoles');

uses(TeamsTestCase::class, RefreshDatabase::class)->in('Features/Teams');

uses(TeamsRolesTestCase::class, RefreshDatabase::class)->in('Features/TeamsRoles');

uses(TeamsUsersTestCase::class, RefreshDatabase::class)->in('Features/TeamsUsers');

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

function currentOtp(User $user): string
{
    return app(Google2FA::class)->getCurrentOtp(Fortify::currentEncrypter()->decrypt($user->two_factor_secret));
}

function enabledTwoFactorUser(bool $confirmed = true): User
{
    $user = User::factory()->create();
    $twoFactor = app(TwoFactorService::class);
    $twoFactor->enable($user);

    if ($confirmed) {
        $twoFactor->confirm($user, currentOtp($user));
        // Confirming used up the current code; tests start with it unused.
        Cache::flush();
    }

    return $user->fresh();
}

/**
 * The contracts package resolves its folder with base_path(), which points into Testbench's skeleton under vendor/.
 */
function packageSnapshotDirectory(): string
{
    $packageRoot = str_replace('\\', '/', dirname(__DIR__));
    $basePath = str_replace('\\', '/', (string) realpath(base_path()));
    $depth = substr_count(trim(substr($basePath, strlen($packageRoot)), '/'), '/') + 1;

    return str_repeat('../', $depth).'tests/snapshots/api';
}

const FIREFOX_ON_WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0';

function insertSession(string $id, User $user, ?string $ip = '10.0.0.1', ?string $userAgent = FIREFOX_ON_WINDOWS): void
{
    DB::table('sessions')->insert(['id' => $id, 'user_id' => $user->id, 'ip_address' => $ip, 'user_agent' => $userAgent, 'payload' => '', 'last_activity' => time()]);
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
/**
 * A user who manages roles directly (not through a role), plus the given report permissions.
 *
 * @param  list<string>  $permissions
 */
function roleManager(array $permissions = []): User
{
    usePermissions(FakePermission::class);
    app(PermissionSynchronizer::class)->sync();

    $user = User::factory()->create();
    $user->givePermissionTo(['roles.view', 'roles.manage', ...$permissions]);

    return $user;
}

/**
 * @return list<string>
 */
function navigationKeys(User $user): array
{
    $keys = [];

    actingAs($user)->get(route('headless.dashboard'))->assertInertia(function (AssertableInertia $page) use (&$keys) {
        $keys = collect($page->toArray()['props']['headless']['navigation'])->flatMap(fn (array $section) => array_column($section['items'], 'key'))->all();
    });

    return $keys;
}

/**
 * A user who holds the given permissions directly, after the registered permissions were synced.
 */
function permittedUser(string ...$permissions): User
{
    app(PermissionSynchronizer::class)->sync();

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

/**
 * The roles and users pages ask for the password first, as the API tokens page does.
 */
function confirmPassword(): void
{
    withSession(['auth.password_confirmed_at' => time()]);
}

function usersAdmin(): User
{
    return permittedUser('users.view', 'users.create', 'users.update', 'users.block', 'users.delete');
}

/**
 * @param  list<string>  $permissions
 */
function makeRole(string $name, array $permissions, ?User $member = null): Role
{
    $role = Role::create(['name' => $name, 'guard_name' => 'web']);
    $role->givePermissionTo($permissions);
    $member?->assignRole($role);

    return $role;
}

function failsOn(string $field): Closure
{
    return fn (ValidationException $exception) => expect($exception->errors())->toHaveKey($field);
}

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
