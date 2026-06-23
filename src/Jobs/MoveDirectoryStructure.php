<?php

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Enums\EventType;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory as ModelDirectory;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Traits\ActionRequest as ActionRequestTrait;
use Webkul\DAM\Traits\Directory as DirectoryTrait;

class MoveDirectoryStructure implements ShouldQueue
{
    use ActionRequestTrait, DirectoryTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function __construct(protected int $directoryId, protected int $newParentId, protected int $userId) {}

    public function handle(): void
    {
        if (! $this->checkedUser($this->userId)) {
            throw new \Exception('User not found');
        }

        $directoryRepository = app(DirectoryRepository::class);

        $directory = $directoryRepository->find($this->directoryId);

        if (! $directory) {
            throw new \Exception(trans('dam::app.admin.dam.index.directory.not-found'));
        }

        $oldRelativePath = $directory->generatePath();
        $oldStoragePath = sprintf('%s/%s', ModelDirectory::ASSETS_DIRECTORY, $oldRelativePath);

        $name = $this->setDirectoryNameForCopy($directory->name, $this->newParentId);

        $newParentDirectory = $directoryRepository->find($this->newParentId);

        $directoryRepository->isDirectoryWritable($newParentDirectory, 'move');

        if (! $newParentDirectory || $newParentDirectory->isDescendantOf($directory) || $directory->id === $newParentDirectory->id) {
            throw new \Exception(trans('dam::app.admin.dam.index.directory.cannot-move'));
        }

        try {
            // Re-parent and the nested-set lft/rgt rebuild of the whole subtree run in one
            // transaction. A partial rebuild would corrupt the tree (some descendants
            // appended under the new parent, others left with stale bounds), so this is
            // all-or-nothing: either the entire subtree relocates or the move rolls back.
            DB::transaction(function () use ($directory, $newParentDirectory, $name) {
                $directory->name = $name;
                $directory->parent()->associate($newParentDirectory)->save();

                // Iterative BFS — rebuilds nested-set lft/rgt for every descendant
                // without recursion risk on deep trees (10k+ dirs = PHP stack overflow).
                $this->rebuildDescendantNodes($directory);
            });

            $directory->refresh();

            $newRelativePath = $directory->generatePath();
            $newStoragePath = sprintf('%s/%s', ModelDirectory::ASSETS_DIRECTORY, $newRelativePath);

            $disk = ModelDirectory::getAssetDisk();

            // S3: no real directories — move every object to its new key.
            // Local: createDirectoryWithStorage handles the rename in one syscall.
            if ($disk === ModelDirectory::ASSETS_DISK_AWS) {
                $this->moveS3Prefix($disk, $oldStoragePath, $newStoragePath);
            }

            // Single REPLACE() updates every asset path in the subtree — 3 queries
            // regardless of asset count, replacing the previous N individual UPDATEs.
            $this->batchReplaceAssetPaths($directory, $oldStoragePath, $newStoragePath);

            $directoryRepository->createDirectoryWithStorage($newRelativePath, $oldRelativePath);

            $this->completed(EventType::MOVE_DIRECTORY_STRUCTURE->value, $this->userId);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Iterative BFS replacement for the former recursive updateDirectoryParentAndChildren.
     * Calls appendToNode(parent) on each descendant to keep nested-set lft/rgt consistent
     * after the root was re-parented. Queue holds [child, parent] pairs so each node is
     * always appended under its correct immediate parent.
     */
    private function rebuildDescendantNodes(ModelDirectory $root): void
    {
        $queue = [];

        foreach ($root->children as $child) {
            $queue[] = [$child, $root];
        }

        while (! empty($queue)) {
            [$node, $parent] = array_shift($queue);

            $node->appendToNode($parent)->save();

            foreach ($node->children as $grandchild) {
                $queue[] = [$grandchild, $node];
            }
        }
    }

    /**
     * Replace asset paths for every asset in the directory subtree in 3 queries:
     * one lft/rgt range scan for descendant IDs, one pivot join for asset IDs,
     * one bulk REPLACE(). Works on both MySQL and PostgreSQL.
     */
    private function batchReplaceAssetPaths(ModelDirectory $directory, string $oldPrefix, string $newPrefix): void
    {
        if ($oldPrefix === $newPrefix) {
            return;
        }

        $subtreeDirIds = $directory->descendants()->pluck('id')->prepend($directory->id);

        $pivotTable = (new Asset)->directories()->getTable();
        $assetIds = DB::table($pivotTable)
            ->whereIn('directory_id', $subtreeDirIds)
            ->distinct()
            ->pluck('asset_id');

        if ($assetIds->isEmpty()) {
            return;
        }

        DB::table((new Asset)->getTable())
            ->whereIn('id', $assetIds)
            ->update([
                'path' => DB::raw(
                    'REPLACE(path, '.DB::getPdo()->quote($oldPrefix).', '.DB::getPdo()->quote($newPrefix).')'
                ),
            ]);
    }

    /**
     * S3-only: move every object under the old prefix to the new prefix.
     * One allFiles() listing + N moves; no per-directory loop needed.
     */
    private function moveS3Prefix(string $disk, string $oldPrefix, string $newPrefix): void
    {
        foreach (Storage::disk($disk)->allFiles($oldPrefix) as $file) {
            $destination = $newPrefix.substr($file, strlen($oldPrefix));

            try {
                Storage::disk($disk)->move($file, $destination);
            } catch (\Throwable $e) {
                Log::error('DAM MoveDirectoryStructure: S3 file move failed', [
                    'from'  => $file,
                    'to'    => $destination,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }
    }
}
