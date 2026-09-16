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

    /**
     * Drop the category mappings whose field no longer exists.
     *
     * The event carries the deleted field's id and fires once the row is gone, so the
     * code `related_field` stores cannot be resolved from it. Matching against the live
     * field codes instead also clears orphans left by earlier deletions, which kept
     * assets undeletable with no screen left to unlink from.
     */
    public function afterDelete(): void
    {
        $liveFieldCodes = $this->categoryFieldRepository->all(['code'])->pluck('code')->all();

        $this->assetResourceMappingRepository->deleteOrphanedMappings(
            AssetResourceMappingRepository::CATEGORY_TYPE_MAPPING,
            $liveFieldCodes
        );
    }
}
