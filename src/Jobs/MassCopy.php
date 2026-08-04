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

    public int $tries = 1;

    public function retryUntil(): \DateTime
    {
        return now()->addSeconds(3600);
    }

    private const MAX_DEPTH = 100;

    private const ASSET_CHUNK_SIZE = 500;

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

                $this->progressTotal += DB::table($pivotTable)
                    ->join($dirTable, "{$dirTable}.id", '=', "{$pivotTable}.directory_id")
                    ->where("{$dirTable}._lft", '>=', $src->_lft)
                    ->where("{$dirTable}._rgt", '<=', $src->_rgt)
                    ->distinct()
                    ->count("{$pivotTable}.asset_id");
            }

            $this->progressTotal = max(1, $this->progressTotal);

            if (! empty($this->assetIds)) {
                $assetTable = (new Asset)->getTable();
                $pivotTable = (new Asset)->directories()->getTable();
                $now = now()->toDateTimeString();

                Storage::disk($disk)->makeDirectory($targetDirPath);

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

                        DB::transaction(function () use ($assetTable, $pivotTable, $rows) {
                            DB::table($assetTable)->insert($rows);

                            $newIds = DB::table($assetTable)
                                ->whereIn('path', array_column($rows, 'path'))
                                ->pluck('id');

                            if ($newIds->isNotEmpty()) {
                                DB::table($pivotTable)->insert(
                                    $newIds->map(fn ($id) => ['asset_id' => $id, 'directory_id' => $this->targetId])->all()
                                );
                            }
                        });

                        $this->progressDone += count($rows);
                        $this->updateProgress(
                            EventType::MASS_COPY->value,
                            $this->userId,
                            (int) min(99, round($this->progressDone / $this->progressTotal * 100))
                        );
                    });
            }

            foreach ($this->dirIds as $id) {
                $source = $copyableSources[$id] ?? null;

                if (! $source) {
                    continue;
                }

                if ($targetDirectory->id === $source->id || $targetDirectory->isDescendantOf($source)) {
                    throw new \RuntimeException(trans('dam::app.admin.dam.index.directory.cannot-copy'));
                }

                $newName = Directory::uniqueName($source->name, $this->targetId);

                $newRoot = Directory::create(['name' => $newName, 'parent_id' => $this->targetId]);
                $this->usedNames[$newRoot->id] = [];
                $newRootStoragePath = $targetDirPath.'/'.$newName;
                $this->deepCopy($source, $newRoot, $newRootStoragePath, 0);
            }

            $this->completed(EventType::MASS_COPY->value, $this->userId);
            $this->clearProgress(EventType::MASS_COPY->value, $this->userId);
        } catch (\Throwable $e) {
            $this->markFailed(EventType::MASS_COPY->value, $this->userId, $e->getMessage());
            $this->clearProgress(EventType::MASS_COPY->value, $this->userId);
        }
    }

    private function deepCopy(Directory $source, Directory $newParent, string $newParentStoragePath, int $initialDepth): void
    {
        $disk = Directory::getAssetDisk();
        $assetTable = (new Asset)->getTable();
        $pivotTable = (new Asset)->directories()->getTable();
        $now = now()->toDateTimeString();

        $queue = [[$source->id, $newParent, $newParentStoragePath, $initialDepth]];

        while (! empty($queue)) {
            [$srcId, $destDir, $destPath, $curDepth] = array_shift($queue);

            if ($curDepth > self::MAX_DEPTH) {
                throw new \RuntimeException('Directory tree exceeds maximum copy depth of '.self::MAX_DEPTH);
            }

            Storage::disk($disk)->makeDirectory($destPath);

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

                    DB::transaction(function () use ($assetTable, $pivotTable, $rows, $destDir) {
                        DB::table($assetTable)->insert($rows);

                        $newIds = DB::table($assetTable)
                            ->whereIn('path', array_column($rows, 'path'))
                            ->pluck('id');

                        if ($newIds->isNotEmpty()) {
                            DB::table($pivotTable)->insert(
                                $newIds->map(fn ($id) => ['asset_id' => $id, 'directory_id' => $destDir->id])->all()
                            );
                        }
                    });

                    $this->progressDone += count($rows);
                    $this->updateProgress(
                        EventType::MASS_COPY->value,
                        $this->userId,
                        (int) min(99, round($this->progressDone / $this->progressTotal * 100))
                    );
                });

            $srcDir = Directory::with('children:id,name,parent_id')->find($srcId);

            if (! $srcDir) {
                continue;
            }

            foreach ($srcDir->children as $child) {
                $newChild = Directory::create(['name' => $child->name, 'parent_id' => $destDir->id]);
                $this->usedNames[$newChild->id] = [];
                $queue[] = [$child->id, $newChild, $destPath.'/'.$newChild->name, $curDepth + 1];
            }
        }
    }

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
