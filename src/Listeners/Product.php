<?php

namespace Webkul\DAM\Listeners;

use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Repositories\AssetResourceMappingRepository;
use Webkul\DAM\Traits\CollectsAssetIds;
use Webkul\Product\Models\ProductProxy;

class Product
{
    use CollectsAssetIds;

    public function __construct(
        protected AssetRepository $assetRepository,
        protected AssetResourceMappingRepository $assetResourceMappingRepository
    ) {}

    /**
     * Maps asset attributes after an import batch, whose bulk upsert fires no product events; accepts ids or a `product_id` wrapper.
     */
    public function afterImportBatch(array $payload): void
    {
        $productIds = array_filter(
            is_array($payload['product_id'] ?? null) ? $payload['product_id'] : $payload
        );

        if (empty($productIds)) {
            return;
        }

        ProductProxy::with('attribute_family')
            ->whereIn('id', $productIds)
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    $this->syncImportedAssetMappings($product);
                }
            });
    }

    protected function syncImportedAssetMappings($product): void
    {
        $family = $product->attribute_family;

        if (! $family) {
            return;
        }

        $assetFields = $family->customAttributes()->where('attributes.type', 'asset')->get();

        foreach ($assetFields as $assetField) {
            $fieldCode = $assetField->code;

            $assetIds = $this->collectAssetIdsAcrossScopes((array) $product->values, $fieldCode);

            if (empty($assetIds)) {
                $this->assetResourceMappingRepository->deleteProductAssetMappings($product->id, $fieldCode);

                continue;
            }

            $assets = $this->assetRepository->findWhereIn('id', $assetIds);

            if (! $assets || $assets->isEmpty()) {
                continue;
            }

            $this->assetResourceMappingRepository->createProductAssetMappings($assets, $product->id, $fieldCode);
        }
    }

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
