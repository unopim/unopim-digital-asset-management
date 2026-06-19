<?php

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Webkul\DAM\Enums\EventType;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory as ModelDirectory;
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
            $this->markFailed(EventType::DELETE_DIRECTORY->value, $this->userId, $e->getMessage());
        }
    }

    /**
     * Delete the directory with the children directory
     */
    public function deleteDirectoryAndChildren(int $directoryId, DirectoryRepository $directoryRepository): void
    {
        $root = $directoryRepository->find($directoryId);

        if (! $root) {
            return;
        }

        $directoryRepository->isDirectoryWritable($root->parent, 'delete');

        $rootPath = $root->generatePath();
        $lft = $root->_lft;
        $rgt = $root->_rgt;
        $width = $rgt - $lft + 1;
        $table = (new ModelDirectory)->getTable();

        // One range query — every directory ID in the subtree including root.
        // Nested-set lft/rgt scan replaces the former unbounded recursion.
        $subtreeDirIds = $root->descendants()->pluck('id')->prepend($root->id);

        // Bulk delete all assets in the subtree — 2 queries instead of N per dir.
        $assetIds = DB::table('dam_asset_directory')
            ->whereIn('directory_id', $subtreeDirIds)
            ->distinct()
            ->pluck('asset_id');

        if ($assetIds->isNotEmpty()) {
            Asset::whereIn('id', $assetIds)->delete();
        }

        // Remove pivot rows for these directories before deleting the directories
        // themselves — otherwise dam_asset_directory.directory_id FK blocks the delete.
        DB::table('dam_asset_directory')->whereIn('directory_id', $subtreeDirIds)->delete();

        // Null out parent_id within the subtree to break the self-referential FK loop.
        // Without this, PostgreSQL rejects a bulk DELETE because rows in the same batch
        // still reference each other via parent_id, causing FK constraint violations.
        DB::table($table)->whereIn('id', $subtreeDirIds)->update(['parent_id' => null]);

        // Bulk delete all directory rows — no FK block now that parent_id is nulled.
        DB::table($table)->whereIn('id', $subtreeDirIds)->delete();

        // Repair the nested-set lft/rgt values for nodes to the right of the deleted
        // subtree — equivalent to what kalnoy does internally on a single-node delete.
        DB::table($table)->where('_rgt', '>', $rgt)->decrement('_rgt', $width);
        DB::table($table)->where('_lft', '>', $rgt)->decrement('_lft', $width);

        // Single storage call removes the entire directory tree — files AND subdirectories.
        $directoryRepository->deleteDirectoryWithStorage($rootPath);
    }
}
