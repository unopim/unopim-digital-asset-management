<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dam_directory_role', function (Blueprint $table) {
            $table->unsignedBigInteger('directory_id');
            $table->unsignedInteger('role_id');
            $table->timestamps();

            $table->primary(['directory_id', 'role_id']);

            $table->foreign('directory_id')
                ->references('id')->on('dam_directories')
                ->cascadeOnDelete();

            $table->foreign('role_id')
                ->references('id')->on('roles')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dam_directory_role');
    }
};
