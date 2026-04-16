<?php

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Enums\EventType;
use Webkul\DAM\Models\Directory as ModelDirectory;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Traits\ActionRequest as ActionRequestTrait;
use Webkul\DAM\Traits\Directory as DirectoryTrait;

class MoveDirectoryStructure
{
    use ActionRequestTrait, DirectoryTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected int $directoryId, protected int $newParentId, protected int $userId) {}

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->checkedUser($this->userId)) {
            throw new \Exception('User not found');
        }

        $directoryRepository = app(DirectoryRepository::class);

        $directory = $directoryRepository->find($this->directoryId);

        if (! $directory) {
            throw new \Exception(trans('dam::app.admin.dam.index.directory.not-found'));
        }

        $oldPath = $directory->generatePath();

        $name = $this->setDirectoryNameForCopy($directory->name, $this->newParentId);

        $newParentDirectory = $directoryRepository->find($this->newParentId);

        $directoryRepository->isDirectoryWritable($newParentDirectory, 'move');

        if ($newParentDirectory && ! $newParentDirectory->isDescendantOf($directory) && $directory->id !== $newParentDirectory->id) {
            $directory->name = $name;
            $directory->parent()->associate($newParentDirectory)->save();
        } else {
            throw new \Exception(trans('dam::app.admin.dam.index.directory.cannot-move'));
        }

        try {
            $this->updateDirectoryParentAndChildren($directory, $directoryRepository);

            $directory->refresh();

            $newPath = $directory->generatePath();

            $directoryRepository->createDirectoryWithStorage($newPath, $oldPath);

            $this->completed(EventType::MOVE_DIRECTORY_STRUCTURE->value, $this->userId);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * update the directory parent with the children directory
     */
    public function updateDirectoryParentAndChildren(ModelDirectory $originalDirectory, $directoryRepository): void
    {
        foreach ($originalDirectory->children as $child) {
            $child->save();

            // Set the new child to the new directory
            $child->appendToNode($originalDirectory)->save();
            $this->updateDirectoryParentAndChildren($child, $directoryRepository);

            $this->moveAssets($child);
        }

        $this->moveAssets($originalDirectory);
    }

    /**
     * Move the assets of the directory
     */
    public function moveAssets(ModelDirectory $directory): void
    {
        $path = $directory->generatePath();
        $disk = ModelDirectory::getAssetDisk();
        // On object stores like S3 there are no real directories, so the
        // folder-level rename performed later is a no-op and assets would be
        // orphaned. Move each file individually for those drivers.
        $movePerFile = $disk === ModelDirectory::ASSETS_DISK_AWS;

        foreach ($directory->assets()->get() as $asset) {
            $oldAssetPath = $asset->path;
            $newAssetPath = sprintf('%s/%s/%s', ModelDirectory::ASSETS_DIRECTORY, $path, $asset->file_name);

            if (
                $movePerFile
                && $oldAssetPath
                && $oldAssetPath !== $newAssetPath
            ) {
                try {
                    if (
                        Storage::disk($disk)->exists($oldAssetPath)
                        && ! Storage::disk($disk)->exists($newAssetPath)
                    ) {
                        Storage::disk($disk)->move($oldAssetPath, $newAssetPath);
                    }
                } catch (\Throwable $e) {
                    Log::error('DAM: failed to move asset file on storage', [
                        'asset_id' => $asset->id,
                        'from'     => $oldAssetPath,
                        'to'       => $newAssetPath,
                        'disk'     => $disk,
                        'error'    => $e->getMessage(),
                    ]);

                    throw $e;
                }
            }

            $asset->update(['path' => $newAssetPath]);
        }
    }
}
