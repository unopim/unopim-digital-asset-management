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
        } elseif (! $this->isAssetEntity()) {
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

    /**
     * A non-bundle import (a plain CSV paired with a separately uploaded asset tree) still has to
     * recreate that tree so the manifest's asset paths resolve. The assets entity is excluded: it
     * creates its own rows from the CSV and would otherwise double-ingest the staged directory.
     */
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

    protected function isAssetEntity(): bool
    {
        return ($this->import->jobInstance?->entity_type ?? null) === 'assets';
    }

    public function completed(): void
    {
        parent::completed();

        $this->discardBundleWorkspace();
    }

    public function cancel(): void
    {
        parent::cancel();

        $this->discardBundleWorkspace();
    }

    protected function discardBundleWorkspace(): void
    {
        Storage::disk('private')->deleteDirectory(AssetBundleReader::EXTRACT_DIRECTORY_PREFIX.$this->import->id);

        Storage::disk('public')->deleteDirectory(AssetBundleReader::MEDIA_DIRECTORY_PREFIX.$this->import->id);
    }
}
