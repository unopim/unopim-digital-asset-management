<?php

namespace Webkul\DAM\Helpers;

use Illuminate\Support\Str;
use Webkul\DAM\Support\AssetBundleReader;
use Webkul\DataTransfer\Helpers\Import as BaseImport;
use Webkul\DataTransfer\Helpers\Sources\AbstractSource;

/**
 * Teaches the import pipeline to accept an export archive in place of a bare data file.
 *
 * Unpacking happens here rather than in a batch because the job track's file path is
 * rewritten to the extracted data file: validation runs once, so every later batch sees
 * an ordinary CSV or Excel path and takes the core route untouched.
 */
class Import extends BaseImport
{
    public function getSource(): ?AbstractSource
    {
        if (! $this->isAssetBundle()) {
            return parent::getSource();
        }

        $dataFilePath = app(AssetBundleReader::class)->prepare($this->import);

        $this->setImport($this->jobTrackRepository->update([
            'file_path' => $dataFilePath,
        ], $this->import->id));

        return parent::getSource();
    }

    protected function isAssetBundle(): bool
    {
        return Str::endsWith(Str::lower((string) $this->import->file_path), '.zip');
    }
}
