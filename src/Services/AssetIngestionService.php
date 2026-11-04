<?php

namespace Webkul\DAM\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Exceptions\ForbiddenMediaException;
use Webkul\DAM\Exceptions\MediaTooLargeException;
use Webkul\DAM\Filesystem\FileStorer;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Jobs\ProcessAssetUpload;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;

class AssetIngestionService
{
    protected const THUMBNAIL_DIRECTORY = 'thumbnails';

    protected const PREVIEW_DIRECTORY = 'preview';

    public function __construct(
        protected AssetPathResolver $pathResolver,
        protected FileStorer $fileStorer,
    ) {}

    /**
     * @param  array<int, string>  $extraDirectoryNames  additional directory names to also map
     */
    public function ingest(
        string $localPath,
        string $damPath,
        ?string $fileName,
        string $conflictStrategy,
        bool $autoTag,
        array $extraDirectoryNames = [],
    ): IngestResult {
        $disk = Directory::getAssetDisk();

        $destinationPath = $this->pathResolver->destinationPath($damPath, $fileName);

        $existing = Asset::where('path', $destinationPath)->first();

        if ($existing && $conflictStrategy === 'skip') {
            return new IngestResult('skipped', $existing);
        }

        $directoryId = $this->pathResolver->resolveDirectoryForPath($damPath);

        if ($existing && $conflictStrategy === 'update_metadata') {
            $this->mapDirectories($existing, $directoryId, $extraDirectoryNames);

            return new IngestResult('updated', $existing);
        }

        $file = $this->makeUploadedFile($localPath, basename($destinationPath));

        $this->guardFile($file, $localPath);

        if ($existing && $conflictStrategy === 'overwrite') {
            Storage::disk($disk)->delete($existing->path);
            $this->clearDerivatives($existing->path, $disk);
        }

        $storedPath = $this->fileStorer->store(
            path: dirname($destinationPath),
            file: $file,
            fileName: basename($destinationPath),
            options: [FileStorer::HASHED_FOLDER_NAME_KEY => false, 'disk' => $disk],
        );

        $payload = [
            'file_name' => basename($destinationPath),
            'file_type' => AssetHelper::getFileType($file),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'path'      => $storedPath,
        ];

        if ($existing) {
            $existing->update($payload);
            $asset = $existing;
            $status = 'updated';
        } else {
            $asset = Asset::create($payload);
            $status = 'created';
        }

        $this->mapDirectories($asset, $directoryId, $extraDirectoryNames);

        ProcessAssetUpload::dispatch($asset->id, null, $autoTag, userId: auth()->id());

        return new IngestResult($status, $asset);
    }

    protected function makeUploadedFile(string $localPath, string $originalName): UploadedFile
    {
        return new UploadedFile($localPath, $originalName, null, null, true);
    }

    protected function guardFile(UploadedFile $file, string $localPath): void
    {
        $maxBytes = AssetHelper::getMaxUploadSizeKb() * 1024;

        if (filesize($localPath) > $maxBytes) {
            throw new MediaTooLargeException('Media file exceeds the maximum allowed size.');
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if (AssetHelper::isForbiddenFile($extension, $file->getMimeType(), $file->getClientOriginalName(), $localPath)) {
            throw new ForbiddenMediaException('Media file type is not allowed.');
        }
    }

    /**
     * @param  array<int, string>  $extraDirectoryNames
     */
    protected function mapDirectories(Asset $asset, int $directoryId, array $extraDirectoryNames): void
    {
        $ids = [$directoryId];

        foreach ($extraDirectoryNames as $name) {
            $name = trim((string) $name);

            if ($name === '') {
                continue;
            }

            $match = Directory::where('name', $name)->first();

            if ($match) {
                $ids[] = $match->id;
            }
        }

        $asset->directories()->syncWithoutDetaching(array_unique($ids));
    }

    protected function clearDerivatives(string $path, string $disk): void
    {
        Storage::disk($disk)->delete(self::THUMBNAIL_DIRECTORY.'/'.$path);

        foreach (Storage::disk($disk)->directories(self::PREVIEW_DIRECTORY) as $sizeDirectory) {
            Storage::disk($disk)->delete($sizeDirectory.'/'.$path);
        }
    }
}
