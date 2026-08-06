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

    protected const THUMBNAIL_DIRECTORY = 'thumbnails';

    protected const PREVIEW_DIRECTORY = 'preview';

    /**
     * @return array<string, int>
     */
    public function ingest(string $sourceRoot): array
    {
        $assetTreeRoot = $this->resolveAssetTreeRoot($sourceRoot);

        if ($assetTreeRoot === null) {
            return [];
        }

        $entries = $this->collect($assetTreeRoot);

        if ($entries === []) {
            return [];
        }

        $existing = Asset::whereIn('path', $entries)->get()->keyBy('path');

        $ingested = [];

        foreach ($entries as $entry) {
            $sourceFile = $assetTreeRoot.DIRECTORY_SEPARATOR.$entry;

            $asset = $existing->get($entry);

            $ingested[$entry] = $asset
                ? $this->refreshAsset($asset, $entry, $sourceFile)
                : $this->createAsset($entry, $sourceFile);
        }

        return $ingested;
    }

    /**
     * An archive compressed from a folder rather than from that folder's contents arrives
     * with the whole tree one level down, which used to leave the asset tree unfound and
     * the import silently ingesting nothing. The wrapping folder is looked through before
     * the tree is declared absent, and whichever directory holds it becomes the root the
     * entries are made relative to, so they still read as the asset paths the rows carry.
     */
    protected function resolveAssetTreeRoot(string $sourceRoot): ?string
    {
        if (! is_dir($sourceRoot)) {
            return null;
        }

        if (is_dir($sourceRoot.DIRECTORY_SEPARATOR.Directory::ASSETS_DIRECTORY)) {
            return $sourceRoot;
        }

        foreach (new \FilesystemIterator($sourceRoot, \FilesystemIterator::SKIP_DOTS) as $candidate) {
            if (! $candidate->isDir() || $candidate->isLink()) {
                continue;
            }

            if (is_dir($candidate->getPathname().DIRECTORY_SEPARATOR.Directory::ASSETS_DIRECTORY)) {
                return $candidate->getPathname();
            }
        }

        return null;
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

        $this->store($entry, $sourceFile);

        $asset = Asset::create($this->fileAttributes($entry, $sourceFile));

        $directory->assets()->attach($asset->id);

        ProcessAssetUpload::dispatch($asset->id);

        return $asset->id;
    }

    /**
     * A path already held by an asset is that asset's, so a bundled binary that differs
     * from the stored one is a new revision rather than a second asset: the row keeps its
     * id and everything referencing it, and only the file behind it moves on.
     *
     * An identical binary is left alone, cached renders included, so re-running an import
     * does not cost a re-render of the whole library.
     */
    protected function refreshAsset(Asset $asset, string $entry, string $sourceFile): int
    {
        if (! $this->hasChanged($entry, $sourceFile)) {
            return $asset->id;
        }

        $this->store($entry, $sourceFile);

        $asset->update($this->fileAttributes($entry, $sourceFile));

        ProcessAssetUpload::dispatch($asset->id);

        return $asset->id;
    }

    protected function hasChanged(string $entry, string $sourceFile): bool
    {
        $disk = Storage::disk(Directory::getAssetDisk());

        if (! $disk->exists($entry)) {
            return true;
        }

        try {
            if ($disk->size($entry) !== filesize($sourceFile)) {
                return true;
            }

            return $disk->checksum($entry) !== md5_file($sourceFile);
        } catch (\Throwable) {
            return true;
        }
    }

    protected function store(string $entry, string $sourceFile): void
    {
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

        $this->purgeDerivatives($entry);
    }

    /**
     * Thumbnails and previews are cached under a path derived from the asset's own path
     * and are served without consulting the binary they were rendered from, so dropping
     * them is what makes a replaced image show up as the replacement.
     */
    protected function purgeDerivatives(string $entry): void
    {
        $disk = Storage::disk(Directory::getAssetDisk());

        $disk->delete(self::THUMBNAIL_DIRECTORY.'/'.$entry);
        $disk->delete(self::THUMBNAIL_DIRECTORY.'/'.$entry.'.jpg');

        foreach ($disk->directories(self::PREVIEW_DIRECTORY) as $sizeDirectory) {
            $disk->delete($sizeDirectory.'/'.$entry);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function fileAttributes(string $entry, string $sourceFile): array
    {
        $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));

        $mimeType = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($sourceFile);

        return [
            'file_name' => basename($entry),
            'file_type' => $this->fileTypeFor($extension, $mimeType),
            'file_size' => (int) filesize($sourceFile),
            'mime_type' => $mimeType,
            'extension' => $extension,
            'path'      => $entry,
        ];
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
