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
        Schema::table('dam_directories', function (Blueprint $table) {
            $table->unsignedInteger('_lft')->after('name');
            $table->unsignedInteger('_rgt')->after('_lft');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dam_directories', function (Blueprint $table) {
            $table->dropColumn(['_lft', '_rgt']);
        });
    }
};
