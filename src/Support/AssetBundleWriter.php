<?php

namespace Webkul\DAM\Support;

use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Directory;

class AssetBundleWriter
{
    public const EXPORT_DISK = 'private';

    /**
     * @var array<string, bool>
     */
    protected array $written = [];

    /**
     * @return bool
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
