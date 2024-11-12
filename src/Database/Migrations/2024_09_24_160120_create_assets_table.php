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
        Schema::create('dam_assets', function (Blueprint $table) {
            $table->id();
            $table->string('file_name')->index();
            $table->enum('file_type', ['image', 'video', 'document', 'audio']);
            $table->bigInteger('file_size');
            $table->string('mime_type')->nullable();
            $table->string('extension')->nullable();
            $table->string('path')->unique()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dam_assets');
    }
};
