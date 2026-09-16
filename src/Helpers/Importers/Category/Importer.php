<?php

namespace Webkul\DAM\Helpers\Importers\Category;

use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Contracts\CategoryField;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DAM\Helpers\Importers\Concerns\ResolvesAssetPaths;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Repositories\AssetResourceMappingRepository;
use Webkul\DataTransfer\Helpers\Importers\Category\Importer as CategoryImporter;
use Webkul\DataTransfer\Helpers\Importers\Category\Storage;
use Webkul\DataTransfer\Helpers\Importers\FieldProcessor;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\DataTransfer\Validators\Import\CategoryRulesExtractor;

class Importer extends CategoryImporter
{
    use ResolvesAssetPaths;

    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected CategoryRepository $categoryRepository,
        protected CategoryFieldRepository $categoryFieldRepository,
        protected Storage $categoryStorage,
        protected AttributeRepository $attributeRepository,
        protected LocaleRepository $localeRepository,
        protected ChannelRepository $channelRepository,
        protected CategoryRulesExtractor $categoryRulesExtractor,
        protected FieldProcessor $fieldProcessor,
        protected AssetRepository $assetRepository,
        protected AssetResourceMappingRepository $assetResourceMappingRepository,
    ) {
        parent::__construct(
            $importBatchRepository,
            $categoryRepository,
            $categoryFieldRepository,
            $categoryStorage,
            $attributeRepository,
            $localeRepository,
            $channelRepository,
            $categoryRulesExtractor,
            $fieldProcessor
        );
    }

    public function prepareCategories(array $rowData, array &$categories): void
    {
        $isCategory = $this->isCategoryExist($rowData['code']);

        $categoryValues = $categories['update'][$rowData['code']]['additional_data'] ?? [];

        if (empty($categoryValues) && $isCategory) {
            $categoryValues = $this->categoryRepository->findOneByField('code', $rowData['code'])?->additional_data ?? [];
        }

        $data = [
            'code'            => $rowData['code'],
            'parent'          => $rowData['parent'],
            'additional_data' => $categoryValues,
        ];

        $categoryFields = $this->getCategoryFields();
        $imageDirPath = $this->import->images_directory_path;

        foreach ($rowData as $field => $value) {
            if (! in_array($field, $categoryFields)) {
                continue;
            }

            $catalogField = $this->categoryFieldRepository->where('code', $field)->first();

            if ($catalogField->type === Asset::ASSET_ATTRIBUTE_TYPE) {
                $data['additional_data']['common'][$field] = $this->resolveAssetFieldValue(
                    (string) $value,
                    $data['additional_data']['common'][$field] ?? ''
                );

                continue;
            }

            $value = $this->fieldProcessor->handleField($catalogField, $value, $imageDirPath);

            if ($catalogField->value_per_locale) {
                $locale = $rowData['locale'] ?? null;
                if ($locale) {
                    $data['additional_data']['locale_specific'][$locale][$field] = $value;
                }
            } else {
                $data['additional_data']['common'][$field] = $value;
            }
        }

        if ($this->isCategoryExist($rowData['code'])) {
            $data['additional_data'] = $this->mergeCategoryFieldValues($data['additional_data'], $categories['update'][$rowData['code']]['additional_data'] ?? []);

            $categories['update'][$rowData['code']] = array_merge($categories['update'][$rowData['code']] ?? [], $data);
        } else {
            $data['additional_data'] = $this->mergeCategoryFieldValues($data['additional_data'], $categories['insert'][$rowData['code']]['additional_data'] ?? []);

            $categories['insert'][$rowData['code']] = array_merge($categories['insert'][$rowData['code']] ?? [], $data);
        }
    }

    /**
     * Resolve an asset column into the ids the category stores.
     *
     * A blank cell unlinks the field; skipping it instead left the old ids in place, so
     * an asset could never be unlinked by import. A path that resolves to nothing keeps
     * the current value — `reportMissingAsset()` already reports it, and a typo should
     * not wipe a working association.
     */
    protected function resolveAssetFieldValue(string $rawValue, string $currentValue): string
    {
        if (trim($rawValue) === '') {
            return '';
        }

        $assets = $this->resolveAssetIds($rawValue);

        return $assets === [] ? $currentValue : implode(',', $assets);
    }

    /**
     * Keep every imported category's Linked Resources in step with its asset fields.
     *
     * The category importer bulk-upserts and dispatches no per-category event, so
     * `Listeners\Category` never runs and stale mapping rows survive an import. This is
     * the category counterpart of `Listeners\Product::afterImportBatch()`.
     */
    public function saveCategories(array $categories): void
    {
        parent::saveCategories($categories);

        $this->syncAssetMappings($categories);
    }

    protected function syncAssetMappings(array $categories): void
    {
        $assetFields = $this->categoryFieldRepository->findWhere([
            'status' => 1,
            'type'   => Asset::ASSET_ATTRIBUTE_TYPE,
        ]);

        if ($assetFields->isEmpty()) {
            return;
        }

        foreach (['update', 'insert'] as $section) {
            foreach ($categories[$section] ?? [] as $code => $category) {
                $categoryId = $this->categoryStorage->get($code);

                if (! $categoryId) {
                    continue;
                }

                $additionalData = $this->normaliseAdditionalData($category['additional_data'] ?? []);

                foreach ($assetFields as $assetField) {
                    $this->syncAssetField((int) $categoryId, $assetField, $additionalData);
                }
            }
        }
    }

    protected function syncAssetField(int $categoryId, CategoryField $assetField, array $additionalData): void
    {
        $fieldCode = $assetField->code;

        $assetIds = $this->collectAssetIdsAcrossScopes($additionalData, $fieldCode);

        if ($assetIds === []) {
            $this->assetResourceMappingRepository->deleteCategoryAssetMappings($categoryId, $fieldCode);

            return;
        }

        $assets = $this->assetRepository->findWhereIn('id', $assetIds);

        if (! $assets || $assets->isEmpty()) {
            $this->assetResourceMappingRepository->deleteCategoryAssetMappings($categoryId, $fieldCode);

            return;
        }

        $this->assetResourceMappingRepository->createCategoryAssetMappings($assets, $categoryId, $fieldCode);
    }

    /**
     * Read the field from every scope the category carries.
     *
     * `prepareCategories()` writes asset ids to `common`, but a locale-scoped field
     * imported before this class existed can still hold values under `locale_specific`,
     * and a queued import has no request to resolve a single locale from.
     *
     * @return list<string>
     */
    protected function collectAssetIdsAcrossScopes(array $additionalData, string $fieldCode): array
    {
        $collected = [];

        $harvest = function ($value) use (&$collected): void {
            if (in_array($value, [null, '', []], true)) {
                return;
            }

            foreach ((is_array($value) ? $value : explode(',', (string) $value)) as $assetId) {
                $assetId = trim((string) $assetId);

                if ($assetId !== '') {
                    $collected[$assetId] = $assetId;
                }
            }
        };

        $harvest($additionalData['common'][$fieldCode] ?? null);

        foreach (($additionalData['locale_specific'] ?? []) as $localeValues) {
            $harvest($localeValues[$fieldCode] ?? null);
        }

        return array_values($collected);
    }

    protected function normaliseAdditionalData(mixed $additionalData): array
    {
        if (is_array($additionalData)) {
            return $additionalData;
        }

        return json_decode((string) $additionalData, true) ?: [];
    }
}
