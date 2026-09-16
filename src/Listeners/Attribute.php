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
     * Drop the product mappings whose attribute no longer exists.
     *
     * Mirrors `Listeners\CategoryField@afterDelete` for the product side: the event
     * carries the deleted attribute's id and fires once the row is gone, so the code
     * `related_field` stores cannot be resolved from it. Matching against the live
     * attribute codes instead also clears orphans left by earlier deletions, which kept
     * assets undeletable with no screen left to unlink from.
     */
    public function afterDelete(): void
    {
        $liveAttributeCodes = $this->attributeRepository->all(['code'])->pluck('code')->all();

        $this->assetResourceMappingRepository
            ->where('type', AssetResourceMappingRepository::PRODUCT_TYPE_MAPPING)
            ->whereNotIn('related_field', $liveAttributeCodes)
            ->delete();
    }
}
