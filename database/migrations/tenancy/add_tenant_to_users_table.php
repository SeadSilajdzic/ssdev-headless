<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 0 is the central level, so existing accounts become central ones; no foreign key, as no tenant 0 exists.
            $table->unsignedBigInteger('tenant_id')->default(0)->after('id');
            $table->dropUnique(['email']);
            $table->unique(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        // Checked before any change, as one email per table cannot hold while two tenants share it.
        $shared = DB::table('users')->select('email')->groupBy('email')->havingRaw('count(*) > 1')->limit(5)->pluck('email');

        if ($shared->isNotEmpty()) {
            throw new RuntimeException('Cannot roll back while an email is used in more than one tenant: '.$shared->implode(', ').'. Delete the duplicate accounts first.');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'email']);
            $table->unique('email');
            $table->dropColumn('tenant_id');
        });
    }
};
