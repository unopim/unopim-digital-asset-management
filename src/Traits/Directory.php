<?php

namespace Webkul\DAM\Traits;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory as ModelDirectory;

trait Directory
{
    /**
     * Sets a unique directory name for copying. If the original name already exists
     */
    protected function setDirectoryNameForCopy(string $originalName, int $parentId): string
    {
        $name = $originalName;

        while (ModelDirectory::where('name', $name)->where('parent_id', $parentId)->exists()) {
            $name = $this->replaceAndIncrementOrdinalCopies($name);
        }

        return $name;
    }

    /**
     * Replaces ordinal copies in a string with incremented numbers.
     */
    protected function replaceAndIncrementOrdinalCopies(string $string): string
    {
        $counts = [];

        $result = preg_replace_callback('/\b(\d+)th copy\b/', function ($matches) use (&$counts) {
            $number = (int) $matches[1];

            if (isset($counts[$number])) {
                $counts[$number]++;
            } else {
                $counts[$number] = $number;
            }

            $counts[$number]++;
            $current = $counts[$number];

            $suffix = 'th';

            return "{$current}{$suffix} copy";
        }, $string);

        return $result === $string ? "{$string} 1th copy" : $result;
    }

    protected function generateUniqueFileName($directory, $fileName)
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $nameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);

        $newFileName = $fileName;
        $counter = 1;

        $disk = ModelDirectory::getAssetDisk();

        do {
            $filePath = sprintf('%s/%s', $directory, $newFileName);

            $fileExistsInStorage = Storage::disk($disk)->exists($filePath);
            $fileExistsInDatabase = Asset::where('path', $filePath)->exists();
            if (! $fileExistsInStorage && ! $fileExistsInDatabase) {
                break;
            }

            $newFileName = $nameWithoutExtension.'('.$counter.').'.$extension;
            $counter++;
        } while (true);

        return $newFileName;
    }

    public function mappedWithDirectory($assetIds, $directoryId): ?ModelDirectory
    {
        $directory = $this->directoryRepository->find($directoryId);

        if (! $directory) {
            return null;
        }

        $directory->assets()->attach($assetIds);

        return $directory;
    }

    public function getMetadata(string $path, string $disk)
    {
        try {
            $storage = Storage::disk($disk);

            if (! $storage->exists($path)) {
                throw new \Exception(trans('dam::app.admin.dam.asset.edit.image-source-not-readable'));
            }

            $fileContent = $storage->get($path);
            $image = (new ImageManager(new Driver))->read($fileContent);
            // Get absolute path (IMPORTANT for exif)
            $fullPath = $storage->path($path);

            // Basic image info
            $imageInfo = getimagesize($fullPath);

            // EXIF data (only works for jpeg/tiff)
            $exif = @exif_read_data($fullPath) ?: [];

            $metadata = [
                'FileName'      => basename($fullPath),
                'FileDateTime'  => (int) filemtime($fullPath),
                'FileSize'      => filesize($fullPath),
                'FileType'      => $imageInfo[2] ?? null,
                'MimeType'      => $imageInfo['mime'] ?? null,
                'SectionsFound' => implode(', ', array_keys($exif)),
                'COMPUTED'      => [
                    'html'    => $imageInfo[3] ?? null,
                    'Height'  => $imageInfo[1] ?? null,
                    'Width'   => $imageInfo[0] ?? null,
                    'IsColor' => $imageInfo['channels'] ?? null,
                ],
            ];

            return [
                'success' => true,
                'data'    => $metadata,
            ];
        } catch (\Exception $e) {

            report($e);

            return [
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.edit.failed-to-read', ['exception' => $e->getMessage()]),
            ];
        }
    }
}
