<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Teams installed from 0.4 lack the column; a teams table created since then already has it.
        if (! Schema::hasTable('teams') || Schema::hasColumn('teams', 'tenant_id')) {
            return;
        }

        Schema::table('teams', function (Blueprint $table) {
            // 0 is the central level, so existing teams stay central ones.
            $table->unsignedBigInteger('tenant_id')->default(0)->index()->after('id');
        });
    }

    public function down(): void {}
};
