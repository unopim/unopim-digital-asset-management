<?php

declare(strict_types=1);

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\DAM\Repositories\AssetResourceMappingRepository;

/**
 * Drop asset-resource mapping rows whose `related_field` no longer matches any live
 * category field or product attribute. Only category-field deletes are auto-cleaned
 * going forward (`Listeners\CategoryField`, `Listeners\Attribute`); installs that
 * deleted a field/attribute before those listeners existed still have orphans
 * blocking asset deletion.
 */
class CleanupOrphanedAssetResourceMappings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->queue = 'dam';
    }

    public function handle(
        CategoryFieldRepository $categoryFieldRepository,
        AttributeRepository $attributeRepository,
        AssetResourceMappingRepository $assetResourceMappingRepository
    ): void {
        $liveFieldCodes = $categoryFieldRepository->all(['code'])->pluck('code')->all();

        $this->dropOrphans(
            $assetResourceMappingRepository,
            AssetResourceMappingRepository::CATEGORY_TYPE_MAPPING,
            $liveFieldCodes
        );

        $liveAttributeCodes = $attributeRepository->all(['code'])->pluck('code')->all();

        $this->dropOrphans(
            $assetResourceMappingRepository,
            AssetResourceMappingRepository::PRODUCT_TYPE_MAPPING,
            $liveAttributeCodes
        );
    }

    /**
     * Delete in bounded batches so a mapping table with millions of rows never holds
     * one giant transaction/lock - a handful of orphans shouldn't block concurrent
     * asset reads/writes while this runs. `DELETE ... LIMIT` isn't portable to
     * PostgreSQL, so batch by id instead.
     */
    protected function dropOrphans(
        AssetResourceMappingRepository $assetResourceMappingRepository,
        string $type,
        array $liveCodes
    ): void {
        do {
            $ids = $assetResourceMappingRepository
                ->where('type', $type)
                ->whereNotIn('related_field', $liveCodes)
                ->limit(1000)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $assetResourceMappingRepository->whereIn('id', $ids)->delete();
        } while (true);
    }
}
