<?php

namespace Webkul\DAM\Listeners;

use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\DAM\Repositories\AssetResourceMappingRepository;

class CategoryField
{
    public function __construct(
        protected CategoryFieldRepository $categoryFieldRepository,
        protected AssetResourceMappingRepository $assetResourceMappingRepository
    ) {}

    public function afterDelete(): void
    {
        $liveFieldCodes = $this->categoryFieldRepository->all(['code'])->pluck('code')->all();

        $this->assetResourceMappingRepository->deleteOrphanedMappings(
            AssetResourceMappingRepository::CATEGORY_TYPE_MAPPING,
            $liveFieldCodes
        );
    }
}
