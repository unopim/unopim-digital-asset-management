<?php

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\DAM\Enums\EventType;
use Webkul\DAM\Models\Directory as ModelDirectory;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Traits\ActionRequest as ActionRequestTrait;
use Webkul\DAM\Traits\Directory as DirectoryTrait;

class CopyDirectoryStructure implements ShouldQueue
{
    use ActionRequestTrait, DirectoryTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected int $directoryId, protected int $userId) {}

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

        try {
            $originalDirectory = $directoryRepository->find($this->directoryId);

            $directoryRepository->isDirectoryWritable($originalDirectory->parent, 'copy');

            if ($originalDirectory) {
                $newDirectory = $originalDirectory->replicate();
                $newDirectory->name = $this->setDirectoryNameForCopy($newDirectory->name, $originalDirectory->parent_id);
                $newDirectory->parent_id = $originalDirectory->parent_id;
                $newDirectory->save();

                $newPath = $newDirectory->generatePath();
                $directoryRepository->createDirectoryWithStorage($newPath);

                $this->copyDirectoryAndChildren($originalDirectory, $newDirectory, $directoryRepository);
            }

            $this->completed(EventType::COPY_DIRECTORY_STRUCTURE->value, $this->userId);
        } catch (\Exception $e) {
            $this->failed(EventType::COPY_DIRECTORY_STRUCTURE->value, $this->userId, $e->getMessage());
        }
    }

    /**
     * Copy the directory with the children directory
     */
    public function copyDirectoryAndChildren(ModelDirectory $originalDirectory, ModelDirectory $newDirectory, $directoryRepository): void
    {
        foreach ($originalDirectory->children as $child) {
            $newChild = $child->replicate();
            $newChild->save();

            // Set the new child to the new directory
            $newChild->appendToNode($newDirectory)->save();
            $newPath = $newChild->generatePath();
            $directoryRepository->createDirectoryWithStorage($newPath);

            $this->copyDirectoryAndChildren($child, $newChild, $directoryRepository);
        }
    }
}
