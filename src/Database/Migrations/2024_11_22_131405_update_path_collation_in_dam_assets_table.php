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
        Schema::table('dam_assets', function (Blueprint $table) {
            $table->string('path')->collation('utf8mb4_bin')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dam_assets', function (Blueprint $table) {
            $table->string('path')->collation('utf8mb4_unicode_ci')->change();
        });
    }
};
