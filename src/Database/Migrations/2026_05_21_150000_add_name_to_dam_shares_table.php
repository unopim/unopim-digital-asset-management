<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dam_shares', function (Blueprint $table) {
            $table->string('name')->nullable()->after('token');
        });
    }

    public function down(): void
    {
        Schema::table('dam_shares', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
