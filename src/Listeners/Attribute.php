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

    public function afterDelete(): void
    {
        $liveAttributeCodes = $this->attributeRepository->all(['code'])->pluck('code')->all();

        $this->assetResourceMappingRepository->deleteOrphanedMappings(
            AssetResourceMappingRepository::PRODUCT_TYPE_MAPPING,
            $liveAttributeCodes
        );
    }
}
