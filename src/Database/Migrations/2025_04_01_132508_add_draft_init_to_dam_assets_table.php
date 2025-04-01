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
        if (Schema::hasTable('dam_assets')) {
            Schema::table('dam_assets', function (Blueprint $table) {
                $table->boolean('draft_init')->default(false)->after('path');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('dam_assets')) {
            Schema::table('dam_assets', function (Blueprint $table) {
                $table->dropColumn('draft_init');
            });
        }
    }
};
