<?php

namespace Webkul\DAM\Helpers\Importers\Category;

use Webkul\DAM\Models\Asset;
use Webkul\DataTransfer\Helpers\Importers\Category\Importer as CategoryImporter;

class Importer extends CategoryImporter
{
    /**
     * {@inheritdoc}
     */
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

        /** additional fields data import  */
        $categoryFields = $this->getCategoryFields();
        $imageDirPath = $this->import->images_directory_path;

        foreach ($rowData as $field => $value) {
            if (! in_array($field, $categoryFields)) {
                continue;
            }

            $catalogField = $this->categoryFieldRepository->where('code', $field)->first();

            if ($catalogField->type === Asset::ASSET_ATTRIBUTE_TYPE) {
                continue;
            }

            $value = $this->fieldProcessor->handleField($catalogField, $value, $imageDirPath);

            if ($catalogField->value_per_locale === self::VALUE_PER_LOCALE) {
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
}
