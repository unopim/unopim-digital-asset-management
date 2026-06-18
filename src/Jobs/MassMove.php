<?php

declare(strict_types=1);

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
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Traits\ActionRequest as ActionRequestTrait;
use Webkul\DAM\Traits\Directory as DirectoryTrait;

class MassMove implements ShouldQueue
{
    use ActionRequestTrait, DirectoryTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    private const ASSET_CHUNK_SIZE = 500;

    public function retryUntil(): \DateTime
    {
        return now()->addSeconds(3600);
    }

    public function __construct(
        protected array $assetIds,
        protected array $dirIds,
        protected int $targetId,
        protected int $userId
    ) {}

    public function handle(): void
    {
        if (! $this->checkedUser($this->userId)) {
            $this->markFailed(EventType::MASS_MOVE->value, $this->userId, 'User not found');

            return;
        }

        try {
            $disk = Directory::getAssetDisk();
            $targetDirectory = Directory::findOrFail($this->targetId);
            $targetDirPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $targetDirectory->generatePath());

            $totalDirs = count($this->dirIds);
            $totalAssets = count($this->assetIds);
            $total = max(1, $totalAssets + $totalDirs);
            $done = 0;

            // ── Assets ──────────────────────────────────────────────────────────
            if (! empty($this->assetIds)) {
                $pivotTable = (new Asset)->directories()->getTable();

                // Seed name-collision cache once — 1 query, persists across all chunks via reference
                $usedNames = Asset::whereHas(
                    'directories',
                    fn ($q) => $q->where('dam_directories.id', $this->targetId)
                )->pluck('file_name')->flip()->toArray();

                Storage::disk($disk)->makeDirectory($targetDirPath);

                // Process in chunks to keep memory bounded for large selections
                Asset::whereIn('id', $this->assetIds)
                    ->with(['directories:id,parent_id'])
                    ->chunk(self::ASSET_CHUNK_SIZE, function ($assets) use ($disk, $targetDirPath, $pivotTable, &$usedNames, &$done, $total) {
                        $updates = [];

                        foreach ($assets as $asset) {
                            if (! $asset->directories->first()) {
                                continue;
                            }

                            $ext = $asset->extension ? '.'.$asset->extension : '';
                            $base = pathinfo($asset->file_name, PATHINFO_FILENAME);
                            $newName = $this->resolveUniqueName($base, $ext, $usedNames);
                            $newPath = $targetDirPath.'/'.$newName;

                            try {
                                if ($asset->path && $asset->path !== $newPath) {
                                    Storage::disk($disk)->move($asset->path, $newPath);
                                }

                                $updates[] = ['id' => $asset->id, 'path' => $newPath, 'file_name' => $newName];
                            } catch (\Throwable $e) {
                                report($e);
                            }
                        }

                        if (empty($updates)) {
                            return;
                        }

                        $successIds = array_column($updates, 'id');

                        DB::table($pivotTable)->whereIn('asset_id', $successIds)->delete();
                        DB::table($pivotTable)->insert(
                            array_map(fn ($id) => ['asset_id' => $id, 'directory_id' => $this->targetId], $successIds)
                        );

                        Asset::upsert($updates, ['id'], ['path', 'file_name']);

                        $done += count($updates);
                        $this->updateProgress(
                            EventType::MASS_MOVE->value,
                            $this->userId,
                            (int) min(99, round($done / $total * 100))
                        );
                    });
            }

            // ── Directories ─────────────────────────────────────────────────────
            if (! empty($this->dirIds)) {
                $directoryRepository = app(DirectoryRepository::class);
                $directoryRepository->isDirectoryWritable($targetDirectory, 'move');

                $directories = Directory::whereIn('id', $this->dirIds)->get()->keyBy('id');

                foreach ($this->dirIds as $id) {
                    $directory = $directories->get($id);

                    if (! $directory || ! $directory->isDeletable()) {
                        continue;
                    }

                    if ($id === $this->targetId || $directory->isAncestorOf($targetDirectory)) {
                        continue;
                    }

                    // Capture relative path (what createDirectoryWithStorage expects) BEFORE tree update
                    $oldRelativePath = $directory->generatePath();
                    $oldStoragePath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $oldRelativePath);

                    // Check name conflicts BEFORE moving (directory still in old parent — no self-conflict)
                    $directory->name = $this->setDirectoryNameForCopy($directory->name, $this->targetId);

                    // appendToNode triggers the NestedSet SQL range update for the entire subtree.
                    // parent()->associate() alone only changes parent_id — lft/rgt stay stale.
                    $directory->appendToNode($targetDirectory)->save();
                    $directory->refresh();

                    $newRelativePath = $directory->generatePath();
                    $newStoragePath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $newRelativePath);

                    // S3: no real directories — move each object using a single prefix listing
                    // Local: createDirectoryWithStorage does rename($old, $new) — instant, no per-file loop
                    if ($disk === Directory::ASSETS_DISK_AWS) {
                        $this->moveS3Prefix($disk, $oldStoragePath, $newStoragePath);
                    }

                    // For local: renames the whole dir tree in one syscall
                    // For S3: cleans up old prefix marker + ensures new one exists
                    $directoryRepository->createDirectoryWithStorage($newRelativePath, $oldRelativePath);

                    // 3 queries to update ALL asset paths in subtree (replaces O(N) per-asset updates)
                    $this->batchReplaceAssetPaths($directory, $oldStoragePath, $newStoragePath);

                    $done++;
                    $this->updateProgress(
                        EventType::MASS_MOVE->value,
                        $this->userId,
                        (int) min(99, round($done / $total * 100))
                    );
                }
            }

            $this->completed(EventType::MASS_MOVE->value, $this->userId);
            $this->clearProgress(EventType::MASS_MOVE->value, $this->userId);
        } catch (\Throwable $e) {
            $this->markFailed(EventType::MASS_MOVE->value, $this->userId, $e->getMessage());
            $this->clearProgress(EventType::MASS_MOVE->value, $this->userId);
        }
    }

    /**
     * S3-only: one allFiles() listing + N moves replaces recursive eager-loading dirs+assets.
     */
    private function moveS3Prefix(string $disk, string $oldPrefix, string $newPrefix): void
    {
        foreach (Storage::disk($disk)->allFiles($oldPrefix) as $file) {
            $destination = $newPrefix.substr($file, strlen($oldPrefix));

            try {
                Storage::disk($disk)->move($file, $destination);
            } catch (\Throwable $e) {
                Log::error('DAM MassMove: S3 file move failed', [
                    'from'  => $file,
                    'to'    => $destination,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }
    }

    /**
     * Replace old storage prefix with new one for every asset in the directory subtree.
     * 3 queries regardless of tree depth or file count.
     */
    private function batchReplaceAssetPaths(Directory $directory, string $oldPrefix, string $newPrefix): void
    {
        if ($oldPrefix === $newPrefix) {
            return;
        }

        // Query 1: descendant dir IDs — single lft/rgt range scan
        $subtreeDirIds = $directory->descendants()->pluck('id')->prepend($directory->id);

        // Query 2: all asset IDs in those dirs via pivot
        $pivotTable = (new Asset)->directories()->getTable();
        $assetIds = DB::table($pivotTable)
            ->whereIn('directory_id', $subtreeDirIds)
            ->distinct()
            ->pluck('asset_id');

        if ($assetIds->isEmpty()) {
            return;
        }

        // Query 3: single REPLACE() — works on both MySQL and PostgreSQL
        DB::table((new Asset)->getTable())
            ->whereIn('id', $assetIds)
            ->update([
                'path' => DB::raw(
                    'REPLACE(path, '.DB::getPdo()->quote($oldPrefix).', '.DB::getPdo()->quote($newPrefix).')'
                ),
            ]);
    }

    /**
     * @param  array<string, true>  $usedNames  passed by reference
     */
    private function resolveUniqueName(string $base, string $ext, array &$usedNames): string
    {
        $candidate = $base.$ext;

        if (! isset($usedNames[$candidate])) {
            $usedNames[$candidate] = true;

            return $candidate;
        }

        $candidate = $base.' (copy)'.$ext;

        if (! isset($usedNames[$candidate])) {
            $usedNames[$candidate] = true;

            return $candidate;
        }

        $i = 1;

        do {
            $candidate = $base.' (copy) ('.$i.')'.$ext;
            $i++;
        } while (isset($usedNames[$candidate]));

        $usedNames[$candidate] = true;

        return $candidate;
    }

    public function failed(\Throwable $e): void
    {
        $this->markFailed(EventType::MASS_MOVE->value, $this->userId, $e->getMessage());
        $this->clearProgress(EventType::MASS_MOVE->value, $this->userId);
    }
}
