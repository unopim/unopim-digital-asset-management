<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dam_explorer_bookmarks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('directory_id');
            $table->string('name', 255);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'directory_id']);
            $table->foreign('user_id')->references('id')->on('admins')->cascadeOnDelete();
            $table->foreign('directory_id')->references('id')->on('dam_directories')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dam_explorer_bookmarks');
    }
};
