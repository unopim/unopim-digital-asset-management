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
        Schema::create('dam_asset_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('dam_assets')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('dam_tags')->onDelete('cascade');
            $table->unique(['asset_id', 'tag_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dam_asset_tag');
    }
};
