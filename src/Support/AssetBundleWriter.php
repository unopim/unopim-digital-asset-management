<?php

namespace Webkul\DAM\Support;

use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Directory;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;

/**
 * Streams DAM asset binaries into the folder an export job is assembling.
 *
 * Assets live on the DAM disk while the export folder is built on the export disk, so
 * this cannot use a same-disk copy. Destination paths are the asset paths verbatim,
 * which is what makes the resulting archive self-describing on import.
 */
class AssetBundleWriter
{
    /**
     * Destinations already written by this instance. An asset referenced by many rows
     * resolves to one destination, so it is streamed once rather than once per reference.
     *
     * @var array<string, bool>
     */
    protected array $written = [];

    /**
     * Copy one asset into the export folder, skipping work already done.
     *
     * @return bool whether this call wrote the file
     */
    public function write(string $sourcePath, string $destinationPath): bool
    {
        if (isset($this->written[$destinationPath])) {
            return false;
        }

        $sourceDisk = Storage::disk(Directory::getAssetDisk());

        if (! $sourceDisk->exists($sourcePath)) {
            return false;
        }

        $targetDisk = Storage::disk(AbstractExporter::EXPORT_DISK);

        // Batches run as separate queued jobs, so the in-memory set alone cannot dedupe
        // an asset already written by a sibling batch of the same export.
        if ($targetDisk->exists($destinationPath)) {
            $this->written[$destinationPath] = true;

            return false;
        }

        $stream = $sourceDisk->readStream($sourcePath);

        if ($stream === false) {
            throw new \RuntimeException("Unable to read asset stream: {$sourcePath}");
        }

        try {
            $targetDisk->writeStream($destinationPath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $this->written[$destinationPath] = true;

        return true;
    }
}
