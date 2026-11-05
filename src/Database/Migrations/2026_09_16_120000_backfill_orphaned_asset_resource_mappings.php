<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\DAM\Repositories\AssetResourceMappingRepository;

return new class extends Migration
{
    /**
     * Each batch commits on its own; PostgreSQL would otherwise hold every row lock until up() finishes.
     */
    public $withinTransaction = false;

    /**
     * Clears mapping rows orphaned by field or attribute deletes made before the cleanup listeners existed.
     */
    public function up(): void
    {
        if (! Schema::hasTable('dam_asset_resource_mappings')) {
            return;
        }

        if (Schema::hasTable('category_fields')) {
            $this->dropOrphans(
                AssetResourceMappingRepository::CATEGORY_TYPE_MAPPING,
                DB::table('category_fields')->pluck('code')
            );
        }

        if (Schema::hasTable('attributes')) {
            $this->dropOrphans(
                AssetResourceMappingRepository::PRODUCT_TYPE_MAPPING,
                DB::table('attributes')->pluck('code')
            );
        }
    }

    /**
     * Batches by id because `DELETE ... LIMIT` is not portable to PostgreSQL.
     */
    protected function dropOrphans(string $type, Collection $liveFieldCodes): void
    {
        do {
            $ids = DB::table('dam_asset_resource_mappings')
                ->where('type', $type)
                ->whereNotIn('related_field', $liveFieldCodes)
                ->limit(1000)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            DB::table('dam_asset_resource_mappings')->whereIn('id', $ids)->delete();
        } while (true);
    }

    /**
     * Irreversible: the deleted rows were already orphans (no live field/attribute),
     * nothing to restore.
     */
    public function down(): void {}
};
