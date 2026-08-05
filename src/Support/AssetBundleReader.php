<?php

namespace Webkul\DAM\Support;

use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Jobs\ProcessAssetUpload;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DataTransfer\Contracts\JobTrack as JobTrackContract;
use Webkul\DataTransfer\Support\SafeZipExtractor;

/**
 * Unpacks an export archive and brings its assets into the DAM before any row is read.
 *
 * The archive produced by a media-enabled export stores each asset under its DAM path,
 * and the data file references those same paths. Ingesting first therefore means the
 * importers' existing path lookups resolve without any separate mapping structure.
 */
class AssetBundleReader
{
    public const ASSET_ENTRY_PREFIX = Directory::ASSETS_DIRECTORY.'/';

    protected const DATA_FILE_EXTENSIONS = ['csv', 'xlsx', 'xls'];

    /**
     * Extract the archive, ingest its assets, and return the data file's path on the
     * private disk, ready to be handed to a CSV or Excel source.
     *
     * @throws \RuntimeException when the archive is unreadable, unsafe, or carries no data file
     */
    public function prepare(JobTrackContract $import): string
    {
        $relativeRoot = 'imports/bundles/'.$import->id;
        $extractPath = Storage::disk('private')->path($relativeRoot);

        $entries = $this->extract($import->file_path, $extractPath);

        $this->ingestAssets($entries, $extractPath);

        return $relativeRoot.'/'.$this->locateDataFile($entries);
    }

    /**
     * @return list<string> relative paths present in the extraction directory
     */
    protected function extract(string $archivePath, string $extractPath): array
    {
        $zip = new \ZipArchive;

        if ($zip->open(Storage::disk('private')->path($archivePath)) !== true) {
            throw new \RuntimeException(trans('dam::app.data-transfer.bundle.invalid-archive'));
        }

        $extractor = $this->extractor();

        $rejection = $extractor->rejectionReason($zip);

        if ($rejection !== null) {
            $zip->close();

            throw new \RuntimeException(trans('dam::app.data-transfer.bundle.'.$rejection['key'], $rejection['replace']));
        }

        $entries = $extractor->extract(
            $zip,
            $extractPath,
            fn (string $path, string $extension, string $staged): bool => $this->isPermittedEntry($path, $extension, $staged)
        );

        $zip->close();

        return $entries;
    }

    /**
     * Apply the same file-type policy a DAM upload goes through, so an archive cannot
     * introduce anything the explorer would have refused.
     */
    protected function isPermittedEntry(string $relativePath, string $extension, string $stagedPath): bool
    {
        if (! str_starts_with($relativePath, self::ASSET_ENTRY_PREFIX)) {
            return in_array($extension, self::DATA_FILE_EXTENSIONS, true);
        }

        $mimeType = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($stagedPath);

        return ! AssetHelper::isForbiddenFile($extension, $mimeType, basename($relativePath), $stagedPath);
    }

    /**
     * @param  list<string>  $entries
     */
    protected function locateDataFile(array $entries): string
    {
        foreach ($entries as $entry) {
            if (str_contains($entry, '/')) {
                continue;
            }

            if (in_array(strtolower(pathinfo($entry, PATHINFO_EXTENSION)), self::DATA_FILE_EXTENSIONS, true)) {
                return $entry;
            }
        }

        throw new \RuntimeException(trans('dam::app.data-transfer.bundle.no-data-file'));
    }

    /**
     * Create a DAM asset for every archived binary, reusing anything already present at
     * the same path so a repeated import neither duplicates rows nor overwrites files
     * someone has since edited in the explorer.
     *
     * @param  list<string>  $entries
     * @return array<string, int> ingested asset ids keyed by DAM path
     */
    protected function ingestAssets(array $entries, string $extractPath): array
    {
        $assetEntries = array_values(array_filter(
            $entries,
            fn (string $entry): bool => str_starts_with($entry, self::ASSET_ENTRY_PREFIX)
        ));

        if ($assetEntries === []) {
            return [];
        }

        $existing = Asset::whereIn('path', $assetEntries)->pluck('id', 'path')->all();

        $ingested = [];

        foreach ($assetEntries as $entry) {
            if (isset($existing[$entry])) {
                $ingested[$entry] = $existing[$entry];

                continue;
            }

            $ingested[$entry] = $this->createAsset($entry, $extractPath.DIRECTORY_SEPARATOR.$entry);
        }

        return $ingested;
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

        $asset = Asset::create([
            'file_name' => basename($entry),
            'file_type' => AssetHelper::getFileTypeUsingExtension($extension),
            'file_size' => (int) filesize($sourceFile),
            'mime_type' => (string) (new \finfo(FILEINFO_MIME_TYPE))->file($sourceFile),
            'extension' => $extension,
            'path'      => $entry,
        ]);

        $directory->assets()->attach($asset->id);

        ProcessAssetUpload::dispatch($asset->id);

        return $asset->id;
    }

    /**
     * Walk the archived path, creating any directory the target DAM is missing, so the
     * source tree is reproduced rather than flattened.
     */
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

    protected function extractor(): SafeZipExtractor
    {
        return new SafeZipExtractor(
            maxEntrySize: (int) config('dam.import_bundle.max_entry_size', 524288000),
            maxTotalSize: (int) config('dam.import_bundle.max_total_size', 5368709120),
            maxEntries: (int) config('dam.import_bundle.max_entries', 50000),
            maxCompressionRatio: (float) config('dam.import_bundle.max_compression_ratio', 200),
        );
    }
}
