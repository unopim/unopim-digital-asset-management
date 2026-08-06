<?php

namespace Webkul\DAM\Support;

use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Jobs\ProcessAssetUpload;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;

class AssetTreeIngestor
{
    public const ASSET_ENTRY_PREFIX = Directory::ASSETS_DIRECTORY.'/';

    protected const STORED_FILE_TYPES = ['image', 'video', 'audio', 'document'];

    /**
     * @return array<string, int>
     */
    public function ingest(string $sourceRoot): array
    {
        $entries = $this->collect($sourceRoot);

        if ($entries === []) {
            return [];
        }

        $existing = Asset::whereIn('path', $entries)->pluck('id', 'path')->all();

        $ingested = [];

        foreach ($entries as $entry) {
            if (isset($existing[$entry])) {
                $ingested[$entry] = $existing[$entry];

                continue;
            }

            $ingested[$entry] = $this->createAsset($entry, $sourceRoot.DIRECTORY_SEPARATOR.$entry);
        }

        return $ingested;
    }

    /**
     * @return list<string>
     */
    protected function collect(string $sourceRoot): array
    {
        $assetRoot = $sourceRoot.DIRECTORY_SEPARATOR.Directory::ASSETS_DIRECTORY;

        if (! is_dir($assetRoot)) {
            return [];
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($assetRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        $entries = [];

        foreach ($files as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }

            $entry = str_replace('\\', '/', substr($file->getPathname(), strlen($sourceRoot) + 1));

            if ($this->isPermitted($entry, $file->getPathname())) {
                $entries[] = $entry;
            }
        }

        sort($entries);

        return $entries;
    }

    protected function isPermitted(string $entry, string $sourceFile): bool
    {
        $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));

        $mimeType = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($sourceFile);

        return ! AssetHelper::isForbiddenFile($extension, $mimeType, basename($entry), $sourceFile);
    }

    protected function createAsset(string $entry, string $sourceFile): int
    {
        $directory = $this->ensureDirectory($entry);

        $stream = fopen($sourceFile, 'rb');

        if ($stream === false) {
            throw new \RuntimeException("Unable to read bundled asset: {$entry}");
        }

        try {
            Storage::disk(Directory::getAssetDisk())->writeStream($entry, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));

        $mimeType = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($sourceFile);

        $asset = Asset::create([
            'file_name' => basename($entry),
            'file_type' => $this->fileTypeFor($extension, $mimeType),
            'file_size' => (int) filesize($sourceFile),
            'mime_type' => $mimeType,
            'extension' => $extension,
            'path'      => $entry,
        ]);

        $directory->assets()->attach($asset->id);

        ProcessAssetUpload::dispatch($asset->id);

        return $asset->id;
    }

    protected function fileTypeFor(string $extension, string $mimeType): string
    {
        $type = AssetHelper::getFileTypeUsingExtension($extension);

        if (in_array($type, self::STORED_FILE_TYPES, true)) {
            return $type;
        }

        foreach (self::STORED_FILE_TYPES as $candidate) {
            if (str_contains($mimeType, $candidate)) {
                return $candidate;
            }
        }

        return 'document';
    }

    protected function ensureDirectory(string $entry): Directory
    {
        $segments = explode('/', trim(substr($entry, strlen(self::ASSET_ENTRY_PREFIX)), '/'));

        array_pop($segments);

        if ($segments === []) {
            throw new \RuntimeException("Bundled asset is not inside a directory: {$entry}");
        }

        $parentId = null;
        $directory = null;

        foreach ($segments as $name) {
            $directory = Directory::firstOrCreate([
                'name'      => $name,
                'parent_id' => $parentId,
            ]);

            $parentId = $directory->id;
        }

        return $directory;
    }
}
