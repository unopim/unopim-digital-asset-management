<?php

namespace Webkul\DAM\Traits;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
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

        // Check if the file exists and modify the file name until a unique one is found
        while (Storage::disk(ModelDirectory::ASSETS_DISK)->exists(sprintf('%s/%s', $directory, $newFileName))) {
            $newFileName = $nameWithoutExtension.'('.$counter.').'.$extension;
            $counter++;
        }

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

    public function getMetadata(string $path, string $disk = 'private')
    {
        try {
            $filePath = Storage::disk($disk)->path($path);

            if (! Storage::disk($disk)->exists($path) || ! is_readable($filePath)) {
                throw new \Exception(trans('dam::app.admin.dam.asset.edit.image-source-not-readable'));
            }

            return [
                'success' => true,
                'data'    => Image::make($filePath)->exif(),
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
