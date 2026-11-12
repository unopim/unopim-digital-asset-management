<?php

namespace Webkul\DAM\Helpers\Exporters\Concerns;

use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Support\AssetBundleWriter;

trait CopiesDamMedia
{
    protected ?AssetBundleWriter $assetBundleWriter = null;

    public function copyMedia(string $sourcePath, string $destinationPath, bool $isAssetField = false): void
    {
        if (! $isAssetField) {
            parent::copyMedia($sourcePath, $destinationPath);

            return;
        }

        $this->assetBundleWriter()->write($sourcePath, $destinationPath);
    }

    public function makePublicUrlMedia(string $filePath, bool $isAssetField = false): string
    {
        if ($isAssetField) {
            return route('admin.dam.file.fetch', ['path' => $filePath]);
        }

        return Storage::url($filePath);
    }

    protected function assetBundleWriter(): AssetBundleWriter
    {
        return $this->assetBundleWriter ??= app(AssetBundleWriter::class);
    }
}
