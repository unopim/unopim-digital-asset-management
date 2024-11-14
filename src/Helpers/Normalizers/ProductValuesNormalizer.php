<?php

namespace Webkul\DAM\Helpers\Normalizers;

use Webkul\Attribute\Services\AttributeService;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\Product\Normalizer\ProductAttributeValuesNormalizer;

class ProductValuesNormalizer extends ProductAttributeValuesNormalizer
{
    /**
     * Constructor for object creation
     */
    public function __construct(
        AttributeService $attributeService,
        protected AssetRepository $assetRepository
    ) {
        parent::__construct($attributeService);
    }

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
                $assets = str_contains($value, ',') ? explode(',', $value) : [$value];

                $assets = $this->assetRepository->findWhereIn('id', $assets)->pluck('path')->toArray();

                $value = implode(', ', $assets);
            }

            $values[$attributeCode] = $value;
        }

        return $values;
    }
}
