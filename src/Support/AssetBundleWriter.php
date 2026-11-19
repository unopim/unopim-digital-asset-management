<?php

namespace Webkul\DAM\Support;

use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Directory;

/**
 * Streams DAM asset binaries into an export folder on another disk, keeping asset paths verbatim for import.
 */
class AssetBundleWriter
{
    /**
     * Named here because released UnoPim exporters use this disk literally.
     */
    public const EXPORT_DISK = 'private';

    /**
     * Destinations already written, so an asset referenced by many rows is streamed once.
     *
     * @var array<string, bool>
     */
    protected array $written = [];

    /**
     * Also checks the destination exists, since sibling batches run as separate jobs.
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

        $targetDisk = Storage::disk(self::EXPORT_DISK);

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
