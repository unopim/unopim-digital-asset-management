<?php

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\DAM\Enums\EventType;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Traits\ActionRequest as ActionRequestTrait;

class DeleteDirectory implements ShouldQueue
{
    use ActionRequestTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
            $this->deleteDirectoryAndChildren($this->directoryId, $directoryRepository);

            $this->completed(EventType::DELETE_DIRECTORY->value, $this->userId);
        } catch (\Exception $e) {
            $this->failed(EventType::DELETE_DIRECTORY->value, $this->userId, $e->getMessage());
        }
    }

    /**
     * Delete the directory with the children directory
     */
    public function deleteDirectoryAndChildren(int $directoryId, DirectoryRepository $directoryRepository): void
    {
        $directory = $directoryRepository->find($directoryId);

        $directoryRepository->isDirectoryWritable($directory->parent, 'delete');

        if ($directory) {
            foreach ($directory->children as $child) {
                $this->deleteDirectoryAndChildren($child->id, $directoryRepository);
            }

            $path = $directory->generatePath();

            $directory->assets()->delete();

            $directory->delete();

            $directoryRepository->deleteDirectoryWithStorage($path);
            $directoryRepository->deleteThumbnailDirectoryWithStorage($path);
        }
    }
}
