<?php

use Composer\InstalledVersions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Ssdev\Headless\Features\Teams\TeamContext;
use Ssdev\Headless\Features\Teams\TeamsModule;
use Workbench\App\Models\User;

it('registers the teams feature with its migrations', function () {
    $module = new TeamsModule;

    expect($module->migrations())->toHaveKeys([TeamsModule::TEAMS_MIGRATION, TeamsModule::PERMISSIONS_MIGRATION])
        ->and(array_filter($module->migrations(), 'is_file'))->toHaveCount(2)
        ->and($module->userTraits())->toBe([])
        ->and($module->permissions())->toBe([]);
});

it('turns on spatie teams mode with the global context', function () {
    expect(config('permission.teams'))->toBeTrue()
        ->and(app(PermissionRegistrar::class)->getPermissionsTeamId())->toBe(TeamContext::GLOBAL);
});

it('keeps existing role assignments global when teams are added', function () {
    $tables = config('permission.table_names');

    foreach (['role_has_permissions', 'model_has_roles', 'model_has_permissions', 'roles', 'permissions'] as $table) {
        Schema::drop($tables[$table]);
    }

    config(['permission.teams' => false]);
    (require InstalledVersions::getInstallPath('spatie/laravel-permission').'/database/migrations/create_permission_tables.php.stub')->up();
    $user = User::factory()->create();
    $roleId = DB::table($tables['roles'])->insertGetId(['name' => 'Editor', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
    DB::table($tables['model_has_roles'])->insert(['role_id' => $roleId, 'model_type' => $user->getMorphClass(), 'model_id' => $user->id]);

    config(['permission.teams' => true]);
    (require __DIR__.'/../../../database/migrations/teams/'.TeamsModule::PERMISSIONS_MIGRATION.'.php')->up();

    expect(DB::table($tables['model_has_roles'])->value('team_id'))->toEqual(TeamContext::GLOBAL)
        ->and(Schema::hasColumn($tables['roles'], 'team_id'))->toBeTrue();
});
