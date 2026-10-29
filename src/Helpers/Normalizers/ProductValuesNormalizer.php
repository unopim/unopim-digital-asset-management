<?php

namespace Webkul\DAM\Helpers\Normalizers;

use Webkul\Attribute\Services\AttributeService;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\Product\Normalizer\ProductAttributeValuesNormalizer;

class ProductValuesNormalizer extends ProductAttributeValuesNormalizer
{
    public function __construct(
        AttributeService $attributeService,
        protected AssetRepository $assetRepository
    ) {
        parent::__construct($attributeService);
    }

    public function normalizeAttributes(array $attributeValues, array $options = []): array
    {
        foreach ($attributeValues as $attributeCode => $value) {
            $attribute = $this->attributeService->findAttributeByCode($attributeCode);

            if (! $attribute || $attribute->type !== 'asset') {
                continue;
            }

            $ids = is_array($value)
                ? $value
                : array_filter(array_map('trim', explode(',', (string) $value)), 'strlen');

            $assetsById = $this->assetRepository
                ->findWhereIn('id', $ids)
                ->keyBy(fn ($asset) => (string) $asset->id);

            $attributeValues[$attributeCode] = collect($ids)
                ->map(fn ($id) => $assetsById->get((string) $id)?->path)
                ->filter()
                ->values()
                ->implode(', ');
        }

        return parent::normalizeAttributes($attributeValues, $options);
    }
}
