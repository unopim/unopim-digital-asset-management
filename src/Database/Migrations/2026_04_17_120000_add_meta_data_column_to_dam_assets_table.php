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
            $table->json('meta_data')->nullable()->after('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dam_assets', function (Blueprint $table) {
            $table->dropColumn('meta_data');
        });
    }
};
