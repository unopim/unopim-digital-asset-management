<?php

namespace Webkul\DAM\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\DAM\Support\AssetBundleReader;
use Webkul\DAM\Support\AssetTreeIngestor;
use Webkul\DataTransfer\Helpers\Import as BaseImport;
use Webkul\DataTransfer\Helpers\Sources\AbstractSource;

class Import extends BaseImport
{
    public function getSource(): ?AbstractSource
    {
        if ($this->isAssetBundle()) {
            $this->openAssetBundle();
        } else {
            $this->ingestUploadedAssetTree();
        }

        return parent::getSource();
    }

    protected function openAssetBundle(): void
    {
        $bundle = app(AssetBundleReader::class)->prepare($this->import);

        $attributes = ['file_path' => $bundle->dataFile];

        if ($bundle->mediaDirectory !== null) {
            $attributes['images_directory_path'] = $bundle->mediaDirectory;
        }

        $this->setImport($this->jobTrackRepository->update($attributes, $this->import->id));
    }

    protected function ingestUploadedAssetTree(): void
    {
        $directory = trim((string) $this->import->images_directory_path, '/');

        if ($directory === '' || str_contains($directory, '..')) {
            return;
        }

        app(AssetTreeIngestor::class)->ingest(Storage::disk('public')->path($directory));
    }

    protected function isAssetBundle(): bool
    {
        return Str::endsWith(Str::lower((string) $this->import->file_path), '.zip');
    }
}
