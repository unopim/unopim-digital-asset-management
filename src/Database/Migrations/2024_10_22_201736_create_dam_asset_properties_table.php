<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dam_asset_properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dam_asset_id');
            $table->string('name');
            $table->string('type');
            $table->string('language');
            $table->text('value');

            $table->timestamps();

            $table->foreign('dam_asset_id')->references('id')->on('dam_assets')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dam_asset_properties');
    }
};
