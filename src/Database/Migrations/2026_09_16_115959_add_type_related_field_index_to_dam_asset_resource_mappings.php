<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dam_asset_resource_mappings', function (Blueprint $table) {
            $table->index(['type', 'related_field']);
        });
    }

    public function down(): void
    {
        Schema::table('dam_asset_resource_mappings', function (Blueprint $table) {
            $table->dropIndex(['type', 'related_field']);
        });
    }
};
