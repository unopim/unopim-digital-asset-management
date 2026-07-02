<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dam_tags', function (Blueprint $table) {
            $table->string('name', 100)->change();
        });
    }

    public function down(): void
    {
        Schema::table('dam_tags', function (Blueprint $table) {
            $table->string('name', 255)->change();
        });
    }
};
