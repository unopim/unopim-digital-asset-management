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

            // if ($attribute->type === 'asset') {
            //     $assets = str_contains($value, ',') ? explode(',', $value) : [$value];

            //     $assets = $this->assetRepository->findWhereIn('id', $assets)->pluck('path')->toArray();

            //     $value = implode(', ', $assets);
            // }

            if ($attribute->type === 'asset') {
                $ids = is_array($value)
                    ? $value
                    : array_filter(array_map('trim', explode(',', (string) $value)), 'strlen');

                $assetsById = $this->assetRepository
                    ->findWhereIn('id', $ids)
                    ->keyBy(fn ($asset) => (string) $asset->id);

                $paths = collect($ids)
                    ->map(fn ($id) => $assetsById->get((string) $id)?->path)
                    ->filter()
                    ->values();

                $value = $paths->implode(', ');
            }

            $values[$attributeCode] = $value;
        }

        return $values;
    }
}
