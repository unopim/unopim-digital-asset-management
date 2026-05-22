<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dam_asset_versions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('asset_id');
            $table->string('version_path', 1024);
            $table->string('original_path', 1024);
            $table->string('original_file_name', 255);
            $table->string('original_extension', 32);
            $table->string('original_mime_type', 127);
            $table->unsignedBigInteger('original_file_size')->default(0);
            $table->timestamps();

            $table->unique('asset_id');
            $table->index('original_path');

            $table->foreign('asset_id')
                ->references('id')
                ->on('dam_assets')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dam_asset_versions');
    }
};
