<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::table('dam_assets', function (Blueprint $table) use ($driver) {
            if ($driver === 'pgsql') {
                $table->string('path')->collation('C')->change();
            } else {
                $table->string('path')->collation('utf8mb4_bin')->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::table('dam_assets', function (Blueprint $table) use ($driver) {
            if ($driver === 'pgsql') {
                $table->string('path')->collation('und-x-icu')->change();
            } else {
                $table->string('path')->collation('utf8mb4_unicode_ci')->change();
            }
        });
    }
};
