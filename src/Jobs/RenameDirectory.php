<?php

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\DAM\Enums\EventType;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Traits\ActionRequest as ActionRequestTrait;

class RenameDirectory implements ShouldQueue
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

        $this->renameAssetDirectory($this->directoryId, $directoryRepository);

        $this->completed(EventType::RENAME_DIRECTORY, $this->userId);
    }

    /**
     * Rename the asset directory
     */
    public function renameAssetDirectory(int $directoryId, DirectoryRepository $directoryRepository): void
    {
        $directory = $directoryRepository->find($directoryId);

        if ($directory) {
            foreach ($directory->children as $child) {
                $this->renameAssetDirectory($child->id, $directoryRepository);
            }

            $path = $directory->generatePath();
            foreach ($directory->assets()->get() as $asset) {
                $asset->update(['path' => sprintf('%s/%s/%s', Directory::ASSETS_DIRECTORY, $path, $asset->file_name)]);
            }
        }
    }
}
