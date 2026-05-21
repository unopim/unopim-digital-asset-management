<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dam_asset_properties', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('is_filterable');
            $table->index(['sort_order', 'name'], 'dam_asset_props_sort_order_idx');
        });
    }

    public function down(): void
    {
        Schema::table('dam_asset_properties', function (Blueprint $table) {
            $table->dropIndex('dam_asset_props_sort_order_idx');
            $table->dropColumn('sort_order');
        });
    }
};
