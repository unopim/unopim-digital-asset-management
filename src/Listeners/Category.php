<?php

namespace Webkul\DAM\Listeners;

use Webkul\Category\Contracts\CategoryField;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Repositories\AssetResourceMappingRepository;

class Category
{
    /**
     * Create a new listener instance.
     *
     * @return void
     */
    public function __construct(
        protected AssetRepository $assetRepository,
        protected CategoryFieldRepository $categoryFieldRepository,
        protected AssetResourceMappingRepository $assetResourceMappingRepository
    ) {}

    /**
     * After category update
     *
     * @param  \Webkul\Category\Contracts\Category  $category
     * @return void
     */
    public function afterUpdateOrCreate($category)
    {
        $activeAssetFields = $this->categoryFieldRepository->findWhere(['status' => 1, 'type' => 'asset']);

        $additionalData = $category->additional_data;

        $categoryId = $category->id;

        $currentLocaleCode = core()->getRequestedLocaleCode();

        foreach ($activeAssetFields as $assetField) {
            $fieldCode = $assetField->code;

            $value = $this->getCategoryValue($additionalData, $assetField, $fieldCode, $currentLocaleCode);

            if (empty($value)) {
                $this->assetResourceMappingRepository->deleteCategoryAssetMappings($categoryId, $fieldCode);

                continue;
            }

            $value = str_contains($value, ',') ? explode(',', $value) : [$value];

            $assets = $this->assetRepository->findWhereIn('id', $value);

            if (! $assets) {
                continue;
            }

            $this->assetResourceMappingRepository->createCategoryAssetMappings($assets, $categoryId, $fieldCode);
        }
    }

    /**
     * Return value from category data
     */
    protected function getCategoryValue(
        array $additionalData,
        CategoryField $field,
        string $fieldCode,
        string $currentLocaleCode
    ): mixed {
        if ($field->isLocaleBasedField()) {
            return $additionalData['locale_specific'][$currentLocaleCode][$fieldCode] ?? null;
        }

        return $additionalData['common'][$fieldCode] ?? null;
    }
}
