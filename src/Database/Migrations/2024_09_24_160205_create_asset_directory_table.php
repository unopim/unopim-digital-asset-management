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
        Schema::create('dam_asset_directory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('dam_assets')->onDelete('cascade');
            $table->foreignId('directory_id')->constrained('dam_directories')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dam_asset_directory');
    }
};
