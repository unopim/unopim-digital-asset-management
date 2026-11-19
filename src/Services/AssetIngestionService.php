<?php

namespace Webkul\DAM\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Exceptions\DirectoryAccessDeniedException;
use Webkul\DAM\Exceptions\ForbiddenMediaException;
use Webkul\DAM\Exceptions\MediaTooLargeException;
use Webkul\DAM\Filesystem\FileStorer;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Jobs\ProcessAssetUpload;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;

class AssetIngestionService
{
    public function __construct(
        protected AssetPathResolver $pathResolver,
        protected FileStorer $fileStorer,
        protected DirectoryPermissionService $permissionService,
    ) {}

    /**
     * @param  array<int, string>  $extraDirectoryNames
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

        $this->assertDirectoryWritable($directoryId);

        if ($existing && $conflictStrategy === 'update_metadata') {
            $this->mapDirectories($existing, $directoryId, $extraDirectoryNames);

            return new IngestResult('updated', $existing);
        }

        $file = $this->makeUploadedFile($localPath, basename($destinationPath));

        $this->guardFile($file, $localPath);

        if ($existing && $conflictStrategy === 'overwrite') {
            Storage::disk($disk)->delete($existing->path);
            AssetHelper::clearDerivatives($existing->path, $disk);
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
     * @param  array<int, string>  $extraDirectoryPaths
     */
    protected function mapDirectories(Asset $asset, int $directoryId, array $extraDirectoryPaths): void
    {
        $ids = [$directoryId];

        foreach ($extraDirectoryPaths as $path) {
            $path = trim((string) $path);

            if ($path === '') {
                continue;
            }

            try {
                $extraId = $this->pathResolver->resolveDirectory($path);

                $this->assertDirectoryWritable($extraId);

                $ids[] = $extraId;
            } catch (DirectoryAccessDeniedException) {
                continue;
            }
        }

        $asset->directories()->syncWithoutDetaching(array_unique($ids));
    }

    protected function assertDirectoryWritable(int $directoryId): void
    {
        if ($this->permissionService->bypass()) {
            return;
        }

        if ($this->pathResolver->wasCreated($directoryId)) {
            return;
        }

        if (! $this->permissionService->canAccess($directoryId)) {
            throw new DirectoryAccessDeniedException('No permission to write into directory id '.$directoryId);
        }
    }
}
