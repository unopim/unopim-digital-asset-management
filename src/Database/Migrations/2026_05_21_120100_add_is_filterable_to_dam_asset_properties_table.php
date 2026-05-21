<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dam_asset_properties', function (Blueprint $table) {
            $table->boolean('is_filterable')->default(false)->after('value');
            $table->index(['is_filterable', 'name'], 'dam_asset_props_filterable_name_idx');
        });
    }

    public function down(): void
    {
        Schema::table('dam_asset_properties', function (Blueprint $table) {
            $table->dropIndex('dam_asset_props_filterable_name_idx');
            $table->dropColumn('is_filterable');
        });
    }
};
