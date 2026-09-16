<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\DAM\Repositories\AssetResourceMappingRepository;

return new class extends Migration
{
    /**
     * Clear mapping rows left behind when a category field or product attribute is
     * deleted, since only category-field deletes are auto-cleaned (`Listeners\CategoryField`,
     * wired to `catalog.category_field.delete.after`) and no listener exists yet for
     * attribute deletes. Orphan rows keep referencing a `related_field` code that no
     * longer exists, which blocks the asset from ever being deleted
     * ("Asset in use. Unlink before deleting").
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
     * Delete in bounded batches so a mapping table with millions of rows never holds
     * one giant transaction/lock - a handful of orphans shouldn't block concurrent
     * asset reads/writes while this runs. `DELETE ... LIMIT` isn't portable to
     * PostgreSQL, so batch by id instead.
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
