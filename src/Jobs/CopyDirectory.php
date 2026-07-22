<?php

declare(strict_types=1);

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Enums\EventType;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Traits\ActionRequest as ActionRequestTrait;

class CopyDirectory implements ShouldQueue
{
    use ActionRequestTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_DEPTH = 100;

    public int $timeout = 3600;

    /** Create a new instance. */
    public function __construct(
        protected int $sourceId,
        protected int $targetId,
        protected int $userId
    ) {}

    /**
     * Copy the source directory and its contents into the target directory.
     */
    public function handle(): void
    {
        if (! $this->checkedUser($this->userId)) {
            $this->markFailed(EventType::COPY_DIRECTORY->value, $this->userId, 'User not found');

            return;
        }

        try {
            $source = Directory::with(['assets', 'children'])->findOrFail($this->sourceId);
            $target = Directory::findOrFail($this->targetId);

            if ($target->id === $source->id || $target->isDescendantOf($source)) {
                throw new \RuntimeException(trans('dam::app.admin.dam.index.directory.cannot-copy'));
            }

            $newName = Directory::uniqueName($source->name, $this->targetId);

            DB::transaction(function () use ($source, $newName) {
                $newRoot = Directory::create(['name' => $newName, 'parent_id' => $this->targetId]);
                $this->deepCopyDirectory($source, $newRoot, 0);
            });

            $this->completed(EventType::COPY_DIRECTORY->value, $this->userId);
        } catch (\Throwable $e) {
            $this->markFailed(EventType::COPY_DIRECTORY->value, $this->userId, $e->getMessage());
        }
    }

    /**
     * Recursively copy a directory, its assets, and child directories.
     */
    private function deepCopyDirectory(Directory $source, Directory $newParent, int $depth): void
    {
        if ($depth > self::MAX_DEPTH) {
            throw new \RuntimeException('Directory tree exceeds maximum copy depth of '.self::MAX_DEPTH);
        }

        $source->loadMissing(['assets', 'children']);

        $disk = Directory::getAssetDisk();
        $newParentStoragePath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $newParent->generatePath());
        Storage::disk($disk)->makeDirectory($newParentStoragePath);

        if ($source->assets->isNotEmpty()) {
            $existingNames = Asset::whereHas(
                'directories',
                fn ($q) => $q->where('dam_directories.id', $newParent->id)
            )->pluck('file_name')->flip()->all();

            $assetRows = [];
            $assetPaths = [];

            foreach ($source->assets as $asset) {
                $newFileName = $this->uniqueAssetNameFromSet($asset->file_name, $existingNames);
                $newStoragePath = $newParentStoragePath.'/'.$newFileName;

                Storage::disk($disk)->copy($asset->path, $newStoragePath);

                $existingNames[$newFileName] = true;

                $assetRows[] = [
                    'file_name'  => $newFileName,
                    'file_type'  => $asset->file_type,
                    'extension'  => $asset->extension,
                    'file_size'  => $asset->file_size,
                    'path'       => $newStoragePath,
                    'mime_type'  => $asset->mime_type,
                    'meta_data'  => $asset->meta_data !== null ? json_encode($asset->meta_data) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $assetPaths[] = $newStoragePath;
            }

            DB::table((new Asset)->getTable())->insert($assetRows);

            $newIds = Asset::whereIn('path', $assetPaths)->pluck('id');

            DB::table('dam_asset_directory')->insert(
                $newIds->map(fn ($id) => ['asset_id' => $id, 'directory_id' => $newParent->id])->all()
            );
        }

        foreach ($source->children as $child) {
            $newChild = Directory::create(['name' => $child->name, 'parent_id' => $newParent->id]);
            $this->deepCopyDirectory($child, $newChild, $depth + 1);
        }
    }

    /**
     * Resolve a unique file name against an in-memory name set.
     */
    private function uniqueAssetNameFromSet(string $fileName, array $existingNames): string
    {
        if (! isset($existingNames[$fileName])) {
            return $fileName;
        }

        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $base = $ext ? substr($fileName, 0, -(strlen($ext) + 1)) : $fileName;
        $dotExt = $ext ? '.'.$ext : '';

        $candidate = $base.' (copy)'.$dotExt;
        if (! isset($existingNames[$candidate])) {
            return $candidate;
        }

        $i = 1;
        do {
            $candidate = $base.' (copy) ('.$i.')'.$dotExt;
            $i++;
        } while (isset($existingNames[$candidate]));

        return $candidate;
    }
}
