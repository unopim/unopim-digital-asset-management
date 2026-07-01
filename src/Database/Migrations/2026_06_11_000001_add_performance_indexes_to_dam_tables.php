<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dam_directories', function (Blueprint $table) {
            $table->index('parent_id');
            $table->index('_lft');
            $table->index('_rgt');
        });

        Schema::table('dam_asset_directory', function (Blueprint $table) {
            $table->index('directory_id');
            $table->index('asset_id');
        });
    }

    public function down(): void
    {
        Schema::table('dam_directories', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['_lft']);
            $table->dropIndex(['_rgt']);
        });

        Schema::table('dam_asset_directory', function (Blueprint $table) {
            $table->dropIndex(['directory_id']);
            $table->dropIndex(['asset_id']);
        });
    }
};
