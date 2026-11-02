<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $roles = config('permission.table_names.roles');
        $team = config('permission.column_names.team_foreign_key') ?? 'team_id';

        if (Schema::hasColumn($roles, 'tenant_id')) {
            return;
        }

        // Teams may have rebuilt the index before this runs, so it is found by its columns rather than its name.
        $unique = collect(Schema::getIndexes($roles))->first(fn (array $index) => $index['unique'] && ! $index['primary'] && in_array('name', $index['columns'], true));
        $columns = array_values(array_filter(['tenant_id', Schema::hasColumn($roles, $team) ? $team : null, 'name', 'guard_name']));

        Schema::table($roles, function (Blueprint $table) use ($unique, $columns) {
            // 0 is the central level, so existing roles stay central ones.
            $table->unsignedBigInteger('tenant_id')->default(0)->after('id');

            if ($unique !== null) {
                $table->dropUnique($unique['name']);
            }

            $table->unique($columns);
        });
    }

    public function down(): void {}
};
