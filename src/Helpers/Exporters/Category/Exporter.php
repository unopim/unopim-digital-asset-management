<?php

namespace Webkul\DAM\Helpers\Exporters\Category;

use Illuminate\Support\Facades\Storage;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Validator\FieldValidator;
use Webkul\DAM\Providers\EventServiceProvider;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DataTransfer\Helpers\Exporters\Category\Exporter as CategoryExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Exporter extends CategoryExporter
{
    /**
     * Create a new instance.
     *
     * @return void
     */
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

    /**
     * Sets category field values for a product. If an category field is not present in the given values array
     */
    protected function setFieldsAdditionalData(array $additionalData, $filePath, $options = [])
    {
        $fieldValues = [];

        $filters = $this->getFilters();

        $withMedia = (bool) $filters['with_media'];

        $mediaSourceType = $filters['media_source_type'] ?? 'zip';

        foreach ($this->categoryFields as $key => $field) {
            $fieldCode = $field->code;
            $fieldType = $field->type;

            $fieldValues[$fieldCode] = $additionalData[$fieldCode] ?? null;

            if ($withMedia && in_array($field->type, $this->mediaTypeFields)) {
                $mediaValues = [];

                $exitingFilePaths = $additionalData[$fieldCode] ?? [];

                $isAssetField = false;

                if ($fieldType === EventServiceProvider::ASSET_ATTRIBUTE_TYPE && is_string($exitingFilePaths)) {
                    $assets = str_contains($exitingFilePaths, ',') ? explode(',', $exitingFilePaths) : [$exitingFilePaths];

                    $exitingFilePaths = $this->assetRepository->findWhereIn('id', $assets)->pluck('path')->toArray();

                    $fieldValues[$fieldCode] = implode(', ', $exitingFilePaths);

                    $isAssetField = true;
                }

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

        return $fieldValues;
    }

    /**
     * Copy media file from a source path to a destination path.
     */
    public function copyMedia(string $sourcePath, string $destinationPath, bool $isAssetField = false)
    {
        if ($isAssetField && Storage::disk('private')->exists($sourcePath)) {
            Storage::writeStream($destinationPath, Storage::disk('private')->readStream($sourcePath));

            return;
        }

        parent::copyMedia($sourcePath, $destinationPath);
    }

    /**
     * Generates a public URL for a given file path
     */
    public function makePublicUrlMedia(string $filePath, bool $isAssetField = false): string
    {
        if ($isAssetField) {
            return route('admin.dam.file.fetch', [$filePath]);
        }

        return Storage::url($filePath);
    }
}
