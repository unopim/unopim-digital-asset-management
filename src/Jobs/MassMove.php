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

            if (! empty($this->assetIds)) {
                $pivotTable = (new Asset)->directories()->getTable();

                $usedNames = Asset::whereHas(
                    'directories',
                    fn ($q) => $q->where('dam_directories.id', $this->targetId)
                )->pluck('file_name')->flip()->toArray();

                Storage::disk($disk)->makeDirectory($targetDirPath);

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

                        DB::transaction(function () use ($pivotTable, $successIds, $updates) {
                            DB::table($pivotTable)->whereIn('asset_id', $successIds)->delete();
                            DB::table($pivotTable)->insert(
                                array_map(fn ($id) => ['asset_id' => $id, 'directory_id' => $this->targetId], $successIds)
                            );

                            $assetTable = DB::getTablePrefix().(new Asset)->getTable();

                            $pathCase = 'CASE id';
                            $nameCase = 'CASE id';
                            $pathBindings = [];
                            $nameBindings = [];

                            foreach ($updates as $update) {
                                $pathCase .= ' WHEN ? THEN ?';
                                $nameCase .= ' WHEN ? THEN ?';
                                $pathBindings[] = $update['id'];
                                $pathBindings[] = $update['path'];
                                $nameBindings[] = $update['id'];
                                $nameBindings[] = $update['file_name'];
                            }

                            $pathCase .= ' END';
                            $nameCase .= ' END';

                            DB::update(
                                sprintf(
                                    'update %s set path = %s, file_name = %s where id in (%s)',
                                    $assetTable,
                                    $pathCase,
                                    $nameCase,
                                    implode(',', array_fill(0, count($successIds), '?'))
                                ),
                                array_merge($pathBindings, $nameBindings, $successIds)
                            );
                        });

                        $done += count($updates);
                        $this->updateProgress(
                            EventType::MASS_MOVE->value,
                            $this->userId,
                            (int) min(99, round($done / $total * 100))
                        );
                    });
            }

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

                    $oldRelativePath = $directory->generatePath();
                    $oldStoragePath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $oldRelativePath);

                    $directory->name = $this->setDirectoryNameForCopy($directory->name, $this->targetId);

                    $directory->appendToNode($targetDirectory)->save();
                    $directory->refresh();

                    $newRelativePath = $directory->generatePath();
                    $newStoragePath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $newRelativePath);

                    if ($disk === Directory::ASSETS_DISK_AWS) {
                        $this->moveS3Prefix($disk, $oldStoragePath, $newStoragePath);
                    }

                    $directoryRepository->createDirectoryWithStorage($newRelativePath, $oldRelativePath);

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

    private function batchReplaceAssetPaths(Directory $directory, string $oldPrefix, string $newPrefix): void
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
