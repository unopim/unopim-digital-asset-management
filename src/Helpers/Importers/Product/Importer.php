<?php

namespace Webkul\DAM\Helpers\Importers\Product;

use Webkul\DAM\Models\Asset;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer as ProductImporter;
use Webkul\DataTransfer\Helpers\Importers\Product\SKUStorage;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DataTransfer\Helpers\Importers\FieldProcessor;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Product\Repositories\ProductRepository;

class Importer extends ProductImporter
{
    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeRepository $attributeRepository,
        protected AttributeOptionRepository $attributeOptionRepository,
        protected CategoryRepository $categoryRepository,
        protected ProductRepository $productRepository,
        protected SKUStorage $skuStorage,
        protected ChannelRepository $channelRepository,
        protected FieldProcessor $fieldProcessor,
        protected AssetRepository $assetRepository
    ) {
        parent::__construct(
            $importBatchRepository,
            $attributeFamilyRepository,
            $attributeRepository,
            $attributeOptionRepository,
            $categoryRepository,
            $productRepository,
            $skuStorage,
            $channelRepository,
            $fieldProcessor
        );
    }

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
                if (! empty($value)) {
                    $values = explode(',', $value);

                    $assets = [];
                    foreach ($values as $value) {
                        $asset = $this->assetRepository->findWhereIn('path', [trim($value)])->first();
                        
                        if ($asset) {
                            $assets[] = $asset->id;
                        }
                    }

                    if ($assets) {
                        $value = implode(',', $assets);

                        $attribute->setProductValue($value, $attributeValues, $rowData['channel'] ?? null, $rowData['locale'] ?? null);
                    }
                }
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
