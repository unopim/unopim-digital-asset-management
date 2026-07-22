<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dam_directories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->foreignId('parent_id')->nullable()->constrained('dam_directories');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dam_directories');
    }
};
