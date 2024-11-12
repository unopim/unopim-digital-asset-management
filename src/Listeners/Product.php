<?php

namespace Webkul\DAM\Listeners;

use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Repositories\AssetResourceMappingRepository;

class Product
{
    /**
     * Create a new listener instance.
     *
     * @return void
     */
    public function __construct(
        protected AssetRepository $assetRepository,
        protected AssetResourceMappingRepository $assetResourceMappingRepository
    ) {}

    /**
     * After product update or create
     */
    public function afterCreateOrupdate($product)
    {
        $productValues = $product->values;

        $productId = $product->id;

        $currentLocaleCode = core()->getRequestedLocaleCode();

        $currentChannelCode = core()->getRequestedChannelCode();

        $activeAssetFields = $product->attribute_family->customAttributes()->where('attributes.type', 'asset')->get();

        foreach ($activeAssetFields as $assetField) {
            $fieldCode = $assetField->code;

            $value = $assetField->getValueFromProductValues($productValues, $currentChannelCode, $currentLocaleCode);

            if (empty($value)) {
                $this->assetResourceMappingRepository->deleteProductAssetMappings($productId, $fieldCode);

                continue;
            }

            if (! is_array($value)) {
                $value = str_contains($value, ',') ? explode(',', $value) : [$value];
            }

            $assets = $this->assetRepository->findWhereIn('id', $value);

            if (! $assets) {
                continue;
            }

            $this->assetResourceMappingRepository->createProductAssetMappings($assets, $productId, $fieldCode);
        }
    }
}
