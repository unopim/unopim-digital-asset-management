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
 * category field or product attribute. Both category-field and attribute deletes are
 * auto-cleaned going forward (`Listeners\CategoryField`, `Listeners\Attribute`);
 * installs that deleted a field/attribute before those listeners existed still have
 * orphans blocking asset deletion, which this job sweeps up on demand.
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

        $assetResourceMappingRepository->deleteOrphanedMappings(
            AssetResourceMappingRepository::CATEGORY_TYPE_MAPPING,
            $liveFieldCodes
        );

        $liveAttributeCodes = $attributeRepository->all(['code'])->pluck('code')->all();

        $assetResourceMappingRepository->deleteOrphanedMappings(
            AssetResourceMappingRepository::PRODUCT_TYPE_MAPPING,
            $liveAttributeCodes
        );
    }
}
