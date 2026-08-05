<?php

namespace Webkul\DAM\Helpers\Exporters\Category;

use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Validator\FieldValidator;
use Webkul\DAM\Helpers\Exporters\Concerns\CopiesDamMedia;
use Webkul\DAM\Providers\EventServiceProvider;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DataTransfer\Helpers\Exporters\Category\Exporter as CategoryExporter;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Exporter extends CategoryExporter
{
    use CopiesDamMedia;

    public function __construct(
        JobTrackBatchRepository $exportBatchRepository,
        FlatItemBuffer $exportFileBuffer,
        CategoryFieldRepository $categoryFieldRepository,
        protected AssetRepository $assetRepository
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer, $categoryFieldRepository);
    }

    protected $mediaTypeFields = [
        FieldValidator::FILE_FIELD_TYPE,
        FieldValidator::IMAGE_FIELD_TYPE,
        EventServiceProvider::ASSET_ATTRIBUTE_TYPE,
    ];

    protected function setFieldsAdditionalData(array $additionalData, $filePath, $options = []): array
    {
        $fieldValues = [];

        $filters = $this->getFilters();

        $withMedia = (bool) ($filters['with_media'] ?? false);

        $mediaSourceType = $filters['media_source_type'] ?? 'zip';

        foreach ($this->categoryFields as $key => $field) {
            $fieldCode = $field->code;
            $fieldType = $field->type;

            $fieldValues[$fieldCode] = EscapeFormulaOperators::escapeValue($additionalData[$fieldCode] ?? null);

            if (in_array($field->type, $this->mediaTypeFields)) {
                $mediaValues = [];

                $exitingFilePaths = $additionalData[$fieldCode] ?? [];

                $isAssetField = false;

                if ($fieldType === EventServiceProvider::ASSET_ATTRIBUTE_TYPE && is_string($exitingFilePaths)) {
                    $assets = str_contains($exitingFilePaths, ',') ? explode(',', $exitingFilePaths) : [$exitingFilePaths];

                    $exitingFilePaths = $this->assetRepository->findWhereIn('id', $assets)->pluck('path')->toArray();

                    $fieldValues[$fieldCode] = implode(', ', $exitingFilePaths);

                    $isAssetField = true;
                }

                if ($withMedia) {
                    $exitingFilePaths = ! is_array($exitingFilePaths) ? [$exitingFilePaths] : $exitingFilePaths;

                    foreach ($exitingFilePaths as $exitingFilePath) {
                        if ($mediaSourceType === 'url') {
                            $mediaValues[] = $this->makePublicUrlMedia($exitingFilePath, $isAssetField);

                            continue;
                        }

                        $newfilePath = $filePath->getTemporaryPath().'/'.$exitingFilePath;

                        $this->copyMedia($exitingFilePath, $newfilePath, $isAssetField);
                    }

                    if (! empty($mediaValues)) {
                        $fieldValues[$fieldCode] = implode(', ', $mediaValues);
                    }
                }
            }

            if (isset($fieldValues[$fieldCode]) && is_array($fieldValues[$fieldCode])) {
                $fieldValues[$fieldCode] = implode(', ', $fieldValues[$fieldCode]);
            }
        }

        return $fieldValues;
    }
}
