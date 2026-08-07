<?php

namespace Webkul\DAM\Listeners;

use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Repositories\AssetResourceMappingRepository;
use Webkul\Product\Models\ProductProxy;

class Product
{
    public function __construct(
        protected AssetRepository $assetRepository,
        protected AssetResourceMappingRepository $assetResourceMappingRepository
    ) {}

    /**
     * Map asset attributes to their products after a bulk import batch.
     *
     * The product importer writes through `DB::table()->upsert()` for speed and never
     * reaches `ProductRepository`, so `catalog.product.create.after` and
     * `catalog.product.update.after` — the events `afterCreateOrupdate()` listens on —
     * are never dispatched for imported rows. Without this handler an imported asset
     * is attached to the product but has no `dam_asset_resource_mappings` row, so the
     * asset's Linked Resources grid comes back empty.
     *
     * The payload carries ids only, never models. `Event::dispatch()` spreads an array
     * payload across the listener's arguments, so the importer's
     * `['product_id' => $ids]` arrives here as `$ids` — the same shape
     * `Webkul\Webhook\Listeners\Product::afterBulkCreate()` receives. The wrapper form
     * is still accepted so a direct call with the documented payload also works.
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

    /**
     * Collect an asset field's ids from every scope the values carry.
     *
     * `afterCreateOrupdate()` resolves a single scope via `core()->getRequestedChannelCode()`
     * and `getRequestedLocaleCode()`, which is right for an admin request but wrong on a
     * queued import: there is no request, so those fall back to defaults that need not
     * match the channel or locale the import wrote. Reading the union of
     * `common`, `locale_specific`, `channel_specific` and `channel_locale_specific`
     * keeps the mapping correct whichever scope the file targeted.
     *
     * @return list<int|string>
     */
    protected function collectAssetIdsAcrossScopes(array $values, string $fieldCode): array
    {
        $collected = [];

        $harvest = function ($value) use (&$collected): void {
            if ($value === null || $value === '' || $value === []) {
                return;
            }

            if (! is_array($value)) {
                $value = str_contains((string) $value, ',')
                    ? explode(',', (string) $value)
                    : [$value];
            }

            foreach ($value as $assetId) {
                $assetId = trim((string) $assetId);

                if ($assetId !== '') {
                    $collected[$assetId] = $assetId;
                }
            }
        };

        $harvest($values['common'][$fieldCode] ?? null);

        foreach (($values['locale_specific'] ?? []) as $localeValues) {
            $harvest($localeValues[$fieldCode] ?? null);
        }

        foreach (($values['channel_specific'] ?? []) as $channelValues) {
            $harvest($channelValues[$fieldCode] ?? null);
        }

        foreach (($values['channel_locale_specific'] ?? []) as $channelValues) {
            foreach ((array) $channelValues as $localeValues) {
                $harvest($localeValues[$fieldCode] ?? null);
            }
        }

        return array_values($collected);
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
