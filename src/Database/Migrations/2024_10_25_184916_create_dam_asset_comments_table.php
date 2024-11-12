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
        Schema::create('dam_asset_comments', function (Blueprint $table) {
            $table->id();
            $table->integer('admin_id')->unsigned()->nullable();
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('SET NULL');
            $table->foreignId('parent_id')->nullable()->constrained('dam_asset_comments')->onDelete('cascade');
            $table->longText('comments');
            $table->unsignedBigInteger('dam_asset_id');
            $table->foreign('dam_asset_id')->references('id')->on('dam_assets')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dam_asset_comments');
    }
};
