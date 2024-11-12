<?php

namespace Webkul\DAM\Helpers\Normalizers;

use Webkul\Product\Normalizer\ProductAttributeValuesNormalizer;

class ProductValuesNormalizer extends ProductAttributeValuesNormalizer
{
    /**
     * Normalize attribute data with options for product
     */
    public function normalizeAttributes(array $attributeValues, array $options = []): array
    {
        $values = [];

        if (empty($options['locale'])) {
            $options['locale'] = core()->getRequestedLocaleCode();
        }

        foreach ($attributeValues as $attributeCode => $value) {
            $attribute = $this->attributeService->findAttributeByCode($attributeCode);

            if (! $attribute) {
                continue;
            }

            if ($attribute->type == 'price' && 'true' == ($options['forExport'] ?? '')) {
                $value = ! is_array($value) ? [] : $value;

                foreach ($value as $currency => $price) {
                    $values["{$attributeCode} ({$currency})"] = $price;
                }

                continue;
            }

            if ($attribute->type === 'asset') {
                $value = implode(', ', $value);
            }

            $values[$attributeCode] = $value;
        }

        return $values;
    }
}
