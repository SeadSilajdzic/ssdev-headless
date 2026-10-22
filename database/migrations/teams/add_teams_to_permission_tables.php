<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Spatie's add_teams_fields stub, except that assignments made before teams existed stay global (team 0)
 * instead of moving into team 1. Tables created with teams on already have the columns and are skipped.
 */
return new class extends Migration
{
    private const GLOBAL_TEAM = 0;

    public function up(): void
    {
        $tables = config('permission.table_names');
        $columns = config('permission.column_names');
        $team = $columns['team_foreign_key'] ?? 'team_id';
        $pivotRole = $columns['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columns['permission_pivot_key'] ?? 'permission_id';
        $morphKey = $columns['model_morph_key'];

        if (! Schema::hasColumn($tables['roles'], $team)) {
            Schema::table($tables['roles'], function (Blueprint $table) use ($team) {
                $table->unsignedBigInteger($team)->nullable()->after('id');
                $table->index($team, 'roles_team_foreign_key_index');
                $table->dropUnique('roles_name_guard_name_unique');
                $table->unique([$team, 'name', 'guard_name']);
            });
        }

        foreach ([[$tables['model_has_permissions'], $pivotPermission, $tables['permissions'], 'model_has_permissions'], [$tables['model_has_roles'], $pivotRole, $tables['roles'], 'model_has_roles']] as [$pivotTable, $pivotKey, $foreignTable, $prefix]) {
            if (Schema::hasColumn($pivotTable, $team)) {
                continue;
            }

            Schema::table($pivotTable, function (Blueprint $table) use ($team, $pivotKey, $foreignTable, $prefix, $morphKey) {
                $table->unsignedBigInteger($team)->default(self::GLOBAL_TEAM);
                $table->index($team, $prefix.'_team_foreign_key_index');

                if (DB::getDriverName() !== 'sqlite') {
                    $table->dropForeign([$pivotKey]);
                }

                $table->dropPrimary();
                $table->primary([$team, $pivotKey, $morphKey, 'model_type'], $prefix.'_'.($prefix === 'model_has_roles' ? 'role' : 'permission').'_model_type_primary');

                if (DB::getDriverName() !== 'sqlite') {
                    $table->foreign($pivotKey)->references('id')->on($foreignTable)->cascadeOnDelete();
                }
            });
        }

        app('cache')->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)->forget(config('permission.cache.key'));
    }

    public function down(): void {}
};
