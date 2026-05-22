<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dam_asset_versions', function (Blueprint $table) {
            $table->string('original_file_type', 32)->nullable()->after('original_mime_type');
            $table->json('original_meta_data')->nullable()->after('original_file_size');
        });
    }

    public function down(): void
    {
        Schema::table('dam_asset_versions', function (Blueprint $table) {
            $table->dropColumn(['original_file_type', 'original_meta_data']);
        });
    }
};
