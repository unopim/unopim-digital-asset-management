<?php

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

            $this->completed(EventType::MOVE_DIRECTORY_STRUCTURE, $this->userId);
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
        foreach ($directory->assets()->get() as $asset) {
            $asset->update(['path' => sprintf('%s/%s/%s', ModelDirectory::ASSETS_DIRECTORY, $path, $asset->file_name)]);
        }
    }
}
