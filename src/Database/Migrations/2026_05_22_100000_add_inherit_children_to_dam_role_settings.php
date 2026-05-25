<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dam_role_settings', function (Blueprint $table) {
            $table->boolean('inherit_children')->default(false)->after('all_directories');
        });
    }

    public function down(): void
    {
        Schema::table('dam_role_settings', function (Blueprint $table) {
            $table->dropColumn('inherit_children');
        });
    }
};
