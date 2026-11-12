<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\DAM\Repositories\AssetResourceMappingRepository;

return new class extends Migration
{
    /**
     * Disable PostgreSQL's default single-transaction wrap so each batch's DELETE
     * commits on its own instead of locking every row until up() finishes.
     */
    public $withinTransaction = false;

    /**
     * Clear mapping rows orphaned by a category field or product attribute deleted
     * before the auto-clean listeners existed. The stale `related_field` code blocks
     * the asset from ever being deleted ("Asset in use. Unlink before deleting").
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
     * Delete in bounded batches, each its own committed transaction (see
     * $withinTransaction above), so a mapping table with millions of rows never holds
     * one giant lock - a handful of orphans shouldn't block concurrent asset
     * reads/writes while this runs. `DELETE ... LIMIT` isn't portable to PostgreSQL,
     * so batch by id instead.
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
