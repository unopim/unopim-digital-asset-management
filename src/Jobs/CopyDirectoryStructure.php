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
            $this->markFailed(EventType::COPY_DIRECTORY_STRUCTURE->value, $this->userId, $e->getMessage());
        }
    }

    /**
     * Copy the directory tree iteratively using BFS.
     *
     * Replaces the former recursive method that would stack-overflow on deep trees.
     * Children for each BFS level are loaded in a single query (one per level)
     * instead of lazy-loading one directory at a time.
     */
    public function copyDirectoryAndChildren(ModelDirectory $root, ModelDirectory $newRoot, DirectoryRepository $directoryRepository): void
    {
        // Each queue entry: [sourceDir, newParentDir]
        $queue = [[$root, $newRoot]];

        while (! empty($queue)) {
            $nextLevel = [];

            // Batch-load all children for this BFS level in one query.
            $sourceIds = array_map(fn ($pair) => $pair[0]->id, $queue);
            $childrenByParent = ModelDirectory::whereIn('parent_id', $sourceIds)
                ->get()
                ->groupBy('parent_id');

            foreach ($queue as [$source, $newParent]) {
                foreach ($childrenByParent->get($source->id, collect()) as $child) {
                    $newChild = $child->replicate();
                    $newChild->appendToNode($newParent)->save();

                    $directoryRepository->createDirectoryWithStorage($newChild->generatePath());

                    $nextLevel[] = [$child, $newChild];
                }
            }

            $queue = $nextLevel;
        }
    }
}
