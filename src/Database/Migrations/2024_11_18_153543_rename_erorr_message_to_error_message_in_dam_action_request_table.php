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
        Schema::table('dam_action_request', function (Blueprint $table) {
            $table->renameColumn('erorr_message', 'error_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dam_action_request', function (Blueprint $table) {
            $table->renameColumn('error_message', 'erorr_message');
        });
    }
};
