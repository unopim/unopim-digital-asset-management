<?php

namespace Webkul\DAM\Helpers\Importers\Product\Concerns;

use Illuminate\Support\Collection;
use Webkul\DAM\Helpers\Importers\Concerns\ResolvesAssetPaths;
use Webkul\DAM\Models\Asset;

/**
 * Resolves the asset paths a data file carries into asset ids on the imported product.
 *
 * Asset columns are stripped from the row before it is handed upward, so the importer
 * being extended never sees a path it has no branch for and would otherwise store
 * verbatim. Its own value handling is left to run untouched.
 */
trait ImportsAssetAttributes
{
    use ResolvesAssetPaths;

    /**
     * Assets are applied after the delegation so their ids overwrite nothing the
     * importer above wrote for the same attribute.
     */
    public function prepareAttributeValues(array $rowData, array &$attributeValues): void
    {
        $assetAttributes = $this->getProductTypeFamilyAttributes(
            $rowData['type'],
            $rowData[self::ATTRIBUTE_FAMILY_CODE]
        )->where('type', Asset::ASSET_ATTRIBUTE_TYPE)->keyBy('code');

        parent::prepareAttributeValues(
            $this->withoutAssetColumns($rowData, $assetAttributes),
            $attributeValues
        );

        foreach ($assetAttributes as $attributeCode => $attribute) {
            $value = $rowData[$attributeCode] ?? null;

            if ($value === null) {
                continue;
            }

            if ($value === '') {
                continue;
            }

            $assets = $this->resolveAssetIds((string) $value);

            if ($assets === []) {
                continue;
            }

            $attribute->setProductValue(
                implode(',', $assets),
                $attributeValues,
                $rowData['channel'] ?? null,
                $rowData['locale'] ?? null
            );
        }
    }

    protected function withoutAssetColumns(array $rowData, Collection $assetAttributes): array
    {
        foreach ($assetAttributes->keys() as $attributeCode) {
            unset($rowData[$attributeCode]);
        }

        return $rowData;
    }
}
