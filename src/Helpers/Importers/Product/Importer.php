<?php

namespace Webkul\DAM\Helpers\Importers\Product;

use Webkul\DAM\Models\Asset;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer as ProductImporter;

class Importer extends ProductImporter
{
    /**
     * {@inheritdoc}
     */
    public function prepareAttributeValues(array $rowData, array &$attributeValues): void
    {
        $familyAttributes = $this->getProductTypeFamilyAttributes($rowData['type'], $rowData[self::ATTRIBUTE_FAMILY_CODE]);
        $imageDirPath = $this->import->images_directory_path;

        foreach ($rowData as $attributeCode => $value) {
            if (is_null($value)) {
                continue;
            }

            /**
             * Since Price column is added like this price (USD) the below function formats and returns the actual attributeCode from the columnName
             */
            [$attributeCode, $currencyCode] = $this->getAttributeCodeAndCurrency($attributeCode);

            $attribute = $familyAttributes->where('code', $attributeCode)->first();

            if (! $attribute) {
                continue;
            }

            if ($attribute->type === 'gallery') {
                $value = explode(',', $value);
            }

            if ($attribute->type === Asset::ASSET_ATTRIBUTE_TYPE) {
                continue;
            }

            $value = $this->fieldProcessor->handleField($attribute, $value, $imageDirPath);

            if ($attribute->type === 'price') {
                $value = $this->formatPriceValueWithCurrency($currencyCode, $value, $attribute->getValueFromProductValues($attributeValues, $rowData['channel'] ?? null, $rowData['locale'] ?? null));
            }

            $attribute->setProductValue($value, $attributeValues, $rowData['channel'] ?? null, $rowData['locale'] ?? null);
        }
    }
}
