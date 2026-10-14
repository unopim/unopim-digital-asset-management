<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original path-collation migration only matched the `mysql` driver,
     * so MariaDB installs never received the case-sensitive collation.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mariadb' || ! Schema::hasTable('dam_assets')) {
            return;
        }

        Schema::table('dam_assets', function (Blueprint $table) {
            $table->string('path')->collation('utf8mb4_bin')->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mariadb' || ! Schema::hasTable('dam_assets')) {
            return;
        }

        Schema::table('dam_assets', function (Blueprint $table) {
            $table->string('path')->collation('utf8mb4_unicode_ci')->change();
        });
    }
};
