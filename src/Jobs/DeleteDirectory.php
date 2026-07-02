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

    /** Create a new instance. */
    public function __construct(protected int $directoryId, protected int $userId) {}

    /**
     * Delete the directory and its children.
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
     * Delete a directory and its children.
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

        $subtreeDirIds = $root->descendants()->pluck('id')->prepend($root->id);

        DB::transaction(function () use ($subtreeDirIds, $table, $rgt, $width) {
            $assetIds = DB::table('dam_asset_directory')
                ->whereIn('directory_id', $subtreeDirIds)
                ->distinct()
                ->pluck('asset_id');

            if ($assetIds->isNotEmpty()) {
                Asset::whereIn('id', $assetIds)->delete();
            }

            DB::table('dam_asset_directory')->whereIn('directory_id', $subtreeDirIds)->delete();

            DB::table($table)->whereIn('id', $subtreeDirIds)->update(['parent_id' => null]);

            DB::table($table)->whereIn('id', $subtreeDirIds)->delete();

            DB::table($table)->where('_rgt', '>', $rgt)->decrement('_rgt', $width);
            DB::table($table)->where('_lft', '>', $rgt)->decrement('_lft', $width);
        });

        $directoryRepository->deleteDirectoryWithStorage($rootPath);
    }
}
