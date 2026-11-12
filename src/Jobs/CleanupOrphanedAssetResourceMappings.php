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

class CleanupOrphanedAssetResourceMappings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->queue = config('dam.queues.dam');
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
