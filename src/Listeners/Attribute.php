<?php

namespace Webkul\DAM\Listeners;

use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\DAM\Repositories\AssetResourceMappingRepository;

class Attribute
{
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected AssetResourceMappingRepository $assetResourceMappingRepository
    ) {}

    /**
     * Matches live attribute codes, since the event fires after the row is gone; this also clears earlier orphans.
     */
    public function afterDelete(): void
    {
        $liveAttributeCodes = $this->attributeRepository->all(['code'])->pluck('code')->all();

        $this->assetResourceMappingRepository->deleteOrphanedMappings(
            AssetResourceMappingRepository::PRODUCT_TYPE_MAPPING,
            $liveAttributeCodes
        );
    }
}
