<?php

namespace Webkul\DAM\Helpers\Exporters\Product;

use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter as BaseExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Exporter extends BaseExporter
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(
        JobTrackBatchRepository $exportBatchRepository,
        FileExportFileBuffer $exportFileBuffer,
        ChannelRepository $channelRepository,
        AttributeRepository $attributeRepository,
        protected AssetRepository $assetRepository
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer, $channelRepository, $attributeRepository);
    }

    /**
     * {@inheritdoc}
     */
    protected function setAttributesValues(array $values, mixed $filePath)
    {
        $attributeValues = [];
        $filters = $this->getFilters();
        $withMedia = (bool) $filters['with_media'];
        $mediaSourceType = $filters['media_source_type'] ?? 'zip';

        foreach ($this->attributes as $key => $attribute) {
            $attributeCode = $attribute->code;

            if ($attributeCode == 'sku') {
                continue;
            }

            $attributeType = $attribute->type;

            $attributeValues[$attributeCode] = $values[$attributeCode] ?? null;

            if ($attributeType == AttributeTypes::PRICE_ATTRIBUTE_TYPE) {
                $priceData = ! empty($attributeValues[$attributeCode]) ? $attributeValues[$attributeCode] : [];

                foreach ($this->currencies as $value) {
                    $attributeValues[$attributeCode.' ('.$value.')'] = $priceData[$value] ?? null;
                }

                unset($attributeValues[$attributeCode]);
            }

            if ($withMedia && in_array($attributeType, [AttributeTypes::FILE_ATTRIBUTE_TYPE, AttributeTypes::IMAGE_ATTRIBUTE_TYPE, 'asset'])) {
                $isAssetField = false;
                $mediaValues = [];

                $exitingFilePaths = $values[$attributeCode] ?? [];

                if ($attributeType === 'asset' && $this->assetRepository && is_string($exitingFilePaths)) {
                    $assets = str_contains($exitingFilePaths, ',') ? explode(',', $exitingFilePaths) : [$exitingFilePaths];

                    $exitingFilePaths = $this->assetRepository->findWhereIn('id', $assets)->pluck('path')->toArray();

                    $attributeValues[$attributeCode] = implode(', ', $exitingFilePaths);

                    $isAssetField = true;
                }

                $exitingFilePaths = ! is_array($exitingFilePaths) ? [$exitingFilePaths] : $exitingFilePaths;

                foreach ($exitingFilePaths as $exitingFilePath) {
                    if ($mediaSourceType == 'url') {
                        $mediaValues[] = $this->makePublicUrlMedia($exitingFilePath, $isAssetField);

                        continue;
                    }

                    $newfilePath = $filePath->getTemporaryPath().'/'.$exitingFilePath;

                    $this->copyMedia($exitingFilePath, $newfilePath, $isAssetField);
                }

                $attributeValues[$attributeCode] = empty($mediaValues) ? $attributeValues[$attributeCode] : implode(', ', $mediaValues);
            }

            if (is_array($attributeValues[$attributeCode] ?? null)) {
                $attributeValues[$attributeCode] = implode(', ', $attributeValues[$attributeCode]);
            }
        }

        return $attributeValues;
    }

    /**
     * Generates a public URL for a given file path
     *
     * @see https://laravel.com/docs/8.x/filesystem#retrieving-files
     */
    public function makePublicUrlMedia(string $filePath, bool $isAssetField = false): string
    {
        if ($isAssetField) {
            return route('admin.dam.file.fetch', ['path' => $filePath]);
        }

        return Storage::url($filePath);
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
}
