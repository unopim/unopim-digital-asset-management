<?php

namespace Webkul\DAM\Helpers\Importers\Product\Concerns;

use Illuminate\Support\Collection;
use Webkul\DAM\Helpers\Importers\Concerns\ResolvesAssetPaths;
use Webkul\DAM\Models\Asset;

trait ImportsAssetAttributes
{
    use ResolvesAssetPaths;

    public function validateRow(array $rowData, int $rowNumber): bool
    {
        $firstValidation = ! isset($this->validatedRows[$rowNumber]);

        if (! parent::validateRow($rowData, $rowNumber)) {
            return false;
        }

        $this->warnAboutMissingAssets(
            $rowData,
            $rowNumber,
            $firstValidation,
            (string) $rowData['sku'],
            $this->assetAttributesFor($rowData)->keys()
        );

        return true;
    }

    public function prepareAttributeValues(array $rowData, array &$attributeValues): void
    {
        $assetAttributes = $this->assetAttributesFor($rowData);

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

    protected function assetAttributesFor(array $rowData): Collection
    {
        return $this->getProductTypeFamilyAttributes(
            $rowData['type'],
            $rowData[self::ATTRIBUTE_FAMILY_CODE]
        )->where('type', Asset::ASSET_ATTRIBUTE_TYPE)->keyBy('code');
    }

    protected function withoutAssetColumns(array $rowData, Collection $assetAttributes): array
    {
        foreach ($assetAttributes->keys() as $attributeCode) {
            unset($rowData[$attributeCode]);
        }

        return $rowData;
    }
}
