<?php

declare(strict_types=1);

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Enums\EventType;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Traits\ActionRequest as ActionRequestTrait;

class MassCopy implements ShouldQueue
{
    use ActionRequestTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    /**
     * Do not retry — this job is not idempotent. A retry after partial completion
     * would re-copy already-created directories/assets, producing duplicates.
     */
    public int $tries = 1;

    public function retryUntil(): \DateTime
    {
        return now()->addSeconds(3600);
    }

    private const MAX_DEPTH = 100;

    private const ASSET_CHUNK_SIZE = 500;

    /** Per-directory name cache: [dirId => [name => true]] — 1 query per dir, then O(1) lookups */
    private array $usedNames = [];

    private int $progressTotal = 0;

    private int $progressDone = 0;

    public function __construct(
        protected array $assetIds,
        protected array $dirIds,
        protected int $targetId,
        protected int $userId
    ) {}

    public function handle(): void
    {
        if (! $this->checkedUser($this->userId)) {
            $this->markFailed(EventType::MASS_COPY->value, $this->userId, 'User not found');
            $this->clearProgress(EventType::MASS_COPY->value, $this->userId);

            return;
        }

        try {
            $disk = Directory::getAssetDisk();
            $targetDirectory = Directory::findOrFail($this->targetId);
            $targetDirPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $targetDirectory->generatePath());

            $copyableSources = [];
            $this->progressTotal = count($this->assetIds);
            $this->progressDone = 0;
            $dirTable = (new Directory)->getTable();
            $pivotTable = (new Asset)->directories()->getTable();

            foreach ($this->dirIds as $id) {
                $src = Directory::find($id);

                if (! $src || ! $src->isCopyable()) {
                    continue;
                }

                $copyableSources[$id] = $src;

                // Count assets in this tree via nested-set range — one fast query per top-level dir
                $this->progressTotal += DB::table($pivotTable)
                    ->join($dirTable, "{$dirTable}.id", '=', "{$pivotTable}.directory_id")
                    ->where("{$dirTable}._lft", '>=', $src->_lft)
                    ->where("{$dirTable}._rgt", '<=', $src->_rgt)
                    ->distinct()
                    ->count("{$pivotTable}.asset_id");
            }

            $this->progressTotal = max(1, $this->progressTotal);

            // ── Assets ──────────────────────────────────────────────────────────
            if (! empty($this->assetIds)) {
                $assetTable = (new Asset)->getTable();
                $pivotTable = (new Asset)->directories()->getTable();
                $now = now()->toDateTimeString();

                Storage::disk($disk)->makeDirectory($targetDirPath);

                // Process in chunks to keep memory bounded for large selections
                Asset::whereIn('id', $this->assetIds)
                    ->select(['file_name', 'file_type', 'extension', 'file_size', 'path', 'mime_type', 'meta_data'])
                    ->chunk(self::ASSET_CHUNK_SIZE, function ($assets) use ($disk, $targetDirPath, $assetTable, $pivotTable, $now) {
                        $rows = [];

                        foreach ($assets as $asset) {
                            if (! $asset->path) {
                                continue;
                            }

                            $ext = $asset->extension ? '.'.$asset->extension : '';
                            $base = pathinfo($asset->file_name, PATHINFO_FILENAME);
                            $newName = $this->resolveUniqueName($base, $ext, $this->targetId);
                            $newPath = $targetDirPath.'/'.$newName;

                            try {
                                Storage::disk($disk)->copy($asset->path, $newPath);
                            } catch (\Throwable $e) {
                                report($e);

                                continue;
                            }

                            $rows[] = [
                                'file_name'  => $newName,
                                'file_type'  => $asset->file_type,
                                'extension'  => $asset->extension,
                                'file_size'  => $asset->file_size,
                                'path'       => $newPath,
                                'mime_type'  => $asset->mime_type,
                                'meta_data'  => $asset->getRawOriginal('meta_data'),
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }

                        if (empty($rows)) {
                            return;
                        }

                        // Bulk insert — 1 query per chunk instead of 1 per asset
                        DB::table($assetTable)->insert($rows);

                        // Retrieve inserted IDs by path (paths unique per disk)
                        $newIds = DB::table($assetTable)
                            ->whereIn('path', array_column($rows, 'path'))
                            ->pluck('id');

                        if ($newIds->isNotEmpty()) {
                            DB::table($pivotTable)->insert(
                                $newIds->map(fn ($id) => ['asset_id' => $id, 'directory_id' => $this->targetId])->all()
                            );
                        }

                        $this->progressDone += count($rows);
                        $this->updateProgress(
                            EventType::MASS_COPY->value,
                            $this->userId,
                            (int) min(99, round($this->progressDone / $this->progressTotal * 100))
                        );
                    });
            }

            // ── Directories ─────────────────────────────────────────────────────
            foreach ($this->dirIds as $id) {
                $source = $copyableSources[$id] ?? null;

                if (! $source) {
                    continue;
                }

                $newName = Directory::uniqueName($source->name, $this->targetId);

                $newRoot = Directory::create(['name' => $newName, 'parent_id' => $this->targetId]);
                $this->usedNames[$newRoot->id] = []; // freshly created — skip DB seed query
                $newRootStoragePath = $targetDirPath.'/'.$newName;
                $this->deepCopy($source, $newRoot, $newRootStoragePath, 0);
                // Note: deepCopy emits progress updates per BFS directory internally
            }

            $this->completed(EventType::MASS_COPY->value, $this->userId);
            $this->clearProgress(EventType::MASS_COPY->value, $this->userId);
        } catch (\Throwable $e) {
            $this->markFailed(EventType::MASS_COPY->value, $this->userId, $e->getMessage());
            $this->clearProgress(EventType::MASS_COPY->value, $this->userId);
        }
    }

    /**
     * Iterative BFS copy — avoids PHP stack overflow on deep/large trees, memory-safe.
     * Bulk DB inserts per chunk (500 assets) instead of one INSERT per asset.
     */
    private function deepCopy(Directory $source, Directory $newParent, string $newParentStoragePath, int $initialDepth): void
    {
        $disk = Directory::getAssetDisk();
        $assetTable = (new Asset)->getTable();
        $pivotTable = (new Asset)->directories()->getTable();
        $now = now()->toDateTimeString();

        // Queue items: [sourceId, destDir, destPath, depth]
        $queue = [[$source->id, $newParent, $newParentStoragePath, $initialDepth]];

        while (! empty($queue)) {
            [$srcId, $destDir, $destPath, $curDepth] = array_shift($queue);

            if ($curDepth > self::MAX_DEPTH) {
                throw new \RuntimeException('Directory tree exceeds maximum copy depth of '.self::MAX_DEPTH);
            }

            Storage::disk($disk)->makeDirectory($destPath);

            // Process assets in chunks — 500 at a time, memory safe for 5k+ assets
            Asset::whereHas('directories', fn ($q) => $q->where('dam_directories.id', $srcId))
                ->select(['file_name', 'file_type', 'extension', 'file_size', 'path', 'mime_type', 'meta_data'])
                ->chunk(self::ASSET_CHUNK_SIZE, function ($assets) use ($disk, $destPath, $destDir, $assetTable, $pivotTable, $now) {
                    $rows = [];

                    foreach ($assets as $asset) {
                        $ext = $asset->extension ? '.'.$asset->extension : '';
                        $base = pathinfo($asset->file_name, PATHINFO_FILENAME);
                        $newName = $this->resolveUniqueName($base, $ext, $destDir->id);
                        $newPath = $destPath.'/'.$newName;

                        try {
                            Storage::disk($disk)->copy($asset->path, $newPath);
                        } catch (\Throwable $e) {
                            report($e);

                            continue;
                        }

                        $rows[] = [
                            'file_name'  => $newName,
                            'file_type'  => $asset->file_type,
                            'extension'  => $asset->extension,
                            'file_size'  => $asset->file_size,
                            'path'       => $newPath,
                            'mime_type'  => $asset->mime_type,
                            'meta_data'  => $asset->getRawOriginal('meta_data'),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if (empty($rows)) {
                        return;
                    }

                    // Bulk insert — 1 query per 500 assets instead of 500 individual INSERTs
                    DB::table($assetTable)->insert($rows);

                    // Retrieve inserted IDs by path (paths unique per disk)
                    $newIds = DB::table($assetTable)
                        ->whereIn('path', array_column($rows, 'path'))
                        ->pluck('id');

                    if ($newIds->isNotEmpty()) {
                        DB::table($pivotTable)->insert(
                            $newIds->map(fn ($id) => ['asset_id' => $id, 'directory_id' => $destDir->id])->all()
                        );
                    }

                    $this->progressDone += count($rows);
                    $this->updateProgress(
                        EventType::MASS_COPY->value,
                        $this->userId,
                        (int) min(99, round($this->progressDone / $this->progressTotal * 100))
                    );
                });

            // Load only direct children (not their assets) for next BFS level
            $srcDir = Directory::with('children:id,name,parent_id')->find($srcId);

            if (! $srcDir) {
                continue;
            }

            foreach ($srcDir->children as $child) {
                $newChild = Directory::create(['name' => $child->name, 'parent_id' => $destDir->id]);
                $this->usedNames[$newChild->id] = []; // freshly created — skip DB seed query
                $queue[] = [$child->id, $newChild, $destPath.'/'.$newChild->name, $curDepth + 1];
            }
        }
    }

    /**
     * Resolve a unique asset name using an in-memory set per directory.
     * 1 query on first call per dir; O(1) hash lookup on every subsequent call.
     */
    private function resolveUniqueName(string $base, string $ext, int $dirId): string
    {
        if (! isset($this->usedNames[$dirId])) {
            $this->usedNames[$dirId] = Asset::whereHas(
                'directories',
                fn ($q) => $q->where('dam_directories.id', $dirId)
            )->pluck('file_name')->flip()->toArray();
        }

        $candidate = $base.$ext;

        if (! isset($this->usedNames[$dirId][$candidate])) {
            $this->usedNames[$dirId][$candidate] = true;

            return $candidate;
        }

        $candidate = $base.' (copy)'.$ext;

        if (! isset($this->usedNames[$dirId][$candidate])) {
            $this->usedNames[$dirId][$candidate] = true;

            return $candidate;
        }

        $i = 1;

        do {
            $candidate = $base.' (copy) ('.$i.')'.$ext;
            $i++;
        } while (isset($this->usedNames[$dirId][$candidate]));

        $this->usedNames[$dirId][$candidate] = true;

        return $candidate;
    }

    public function failed(\Throwable $e): void
    {
        $this->markFailed(EventType::MASS_COPY->value, $this->userId, $e->getMessage());
        $this->clearProgress(EventType::MASS_COPY->value, $this->userId);
    }
}
