<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MariaDB is handled by 2026_10_14_000000_update_path_collation_in_dam_assets_table_for_mariadb.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('dam_assets', function (Blueprint $table) {
            $table->string('path')->collation('utf8mb4_bin')->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('dam_assets', function (Blueprint $table) {
            $table->string('path')->collation('utf8mb4_unicode_ci')->change();
        });
    }
};
