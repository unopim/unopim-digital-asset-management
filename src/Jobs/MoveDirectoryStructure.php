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
            DB::transaction(function () use ($directory, $newParentDirectory, $name) {
                $directory->name = $name;
                $directory->parent()->associate($newParentDirectory)->save();

                $this->rebuildDescendantNodes($directory);
            });

            $directory->refresh();

            $newRelativePath = $directory->generatePath();
            $newStoragePath = sprintf('%s/%s', ModelDirectory::ASSETS_DIRECTORY, $newRelativePath);

            $disk = ModelDirectory::getAssetDisk();

            if ($disk === ModelDirectory::ASSETS_DISK_AWS) {
                $this->moveS3Prefix($disk, $oldStoragePath, $newStoragePath);
            }

            $this->batchReplaceAssetPaths($directory, $oldStoragePath, $newStoragePath);

            $directoryRepository->createDirectoryWithStorage($newRelativePath, $oldRelativePath);

            $this->completed(EventType::MOVE_DIRECTORY_STRUCTURE->value, $this->userId);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Rebuild nested-set bounds for every descendant after re-parenting.
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
     * Bulk-replace the storage path prefix for every asset in the subtree.
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
     * Move every S3 object from the old prefix to the new prefix.
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
