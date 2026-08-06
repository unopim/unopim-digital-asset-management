<?php

namespace Webkul\DAM\Support;

use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DataTransfer\Contracts\JobTrack as JobTrackContract;

class AssetBundleReader
{
    public const MEDIA_DIRECTORY_PREFIX = 'import-bundles/';

    protected const DATA_FILE_EXTENSIONS = ['csv', 'xlsx', 'xls'];

    public function __construct(protected AssetTreeIngestor $assetTreeIngestor) {}

    /**
     * @throws \RuntimeException when the archive is unreadable, unsafe, or carries no data file
     */
    public function prepare(JobTrackContract $import): PreparedBundle
    {
        $relativeRoot = 'imports/bundles/'.$import->id;
        $extractPath = Storage::disk('private')->path($relativeRoot);

        $entries = $this->extract($import->file_path, $extractPath);

        $dataFile = $this->locateDataFile($entries);

        $this->assetTreeIngestor->ingest($extractPath);

        return new PreparedBundle(
            $relativeRoot.'/'.$dataFile,
            $this->stageMedia($entries, $dataFile, $extractPath, $import->id),
        );
    }

    /**
     * @return list<string>
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

    protected function isPermittedEntry(string $relativePath, string $extension, string $stagedPath): bool
    {
        if (! str_contains($relativePath, '/')) {
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
     * @param  list<string>  $entries
     */
    protected function stageMedia(array $entries, string $dataFile, string $extractPath, int $trackId): ?string
    {
        $directory = self::MEDIA_DIRECTORY_PREFIX.$trackId;

        $disk = Storage::disk('public');

        $staged = 0;

        foreach ($entries as $entry) {
            if ($entry === $dataFile || str_starts_with($entry, AssetTreeIngestor::ASSET_ENTRY_PREFIX)) {
                continue;
            }

            $sourceFile = $extractPath.DIRECTORY_SEPARATOR.$entry;

            if (! $this->isServableMedia($entry, $sourceFile)) {
                continue;
            }

            $stream = fopen($sourceFile, 'rb');

            if ($stream === false) {
                continue;
            }

            try {
                $disk->writeStream($directory.'/'.$entry, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $staged++;
        }

        return $staged > 0 ? $directory : null;
    }

    protected function isServableMedia(string $relativePath, string $sourceFile): bool
    {
        return ServableMedia::permits(
            strtolower(pathinfo($relativePath, PATHINFO_EXTENSION)),
            $sourceFile,
            basename($relativePath)
        );
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
