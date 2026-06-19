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

class CopyDirectory implements ShouldQueue
{
    use ActionRequestTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_DEPTH = 100;

    public int $timeout = 3600;

    public function __construct(
        protected int $sourceId,
        protected int $targetId,
        protected int $userId
    ) {}

    public function handle(): void
    {
        if (! $this->checkedUser($this->userId)) {
            $this->markFailed(EventType::COPY_DIRECTORY->value, $this->userId, 'User not found');

            return;
        }

        try {
            $source = Directory::with(['assets', 'children'])->findOrFail($this->sourceId);
            $newName = Directory::uniqueName($source->name, $this->targetId);

            DB::transaction(function () use ($source, $newName) {
                $newRoot = Directory::create(['name' => $newName, 'parent_id' => $this->targetId]);
                $this->deepCopyDirectory($source, $newRoot, 0);
            });

            $this->completed(EventType::COPY_DIRECTORY->value, $this->userId);
        } catch (\Throwable $e) {
            $this->markFailed(EventType::COPY_DIRECTORY->value, $this->userId, $e->getMessage());
        }
    }

    private function deepCopyDirectory(Directory $source, Directory $newParent, int $depth): void
    {
        if ($depth > self::MAX_DEPTH) {
            throw new \RuntimeException('Directory tree exceeds maximum copy depth of '.self::MAX_DEPTH);
        }

        $source->loadMissing(['assets', 'children']);

        $disk = Directory::getAssetDisk();
        $newParentStoragePath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $newParent->generatePath());
        Storage::disk($disk)->makeDirectory($newParentStoragePath);

        if ($source->assets->isNotEmpty()) {
            // Load all existing names in target dir once — O(1) set lookup per asset
            // instead of the former N EXISTS queries (one per asset + conflict retries).
            $existingNames = Asset::whereHas(
                'directories',
                fn ($q) => $q->where('dam_directories.id', $newParent->id)
            )->pluck('file_name')->flip()->all();

            $assetRows = [];
            $assetPaths = [];

            foreach ($source->assets as $asset) {
                $newFileName = $this->uniqueAssetNameFromSet($asset->file_name, $existingNames);
                $newStoragePath = $newParentStoragePath.'/'.$newFileName;

                Storage::disk($disk)->copy($asset->path, $newStoragePath);

                // Register in set so subsequent assets in the same loop avoid collision.
                $existingNames[$newFileName] = true;

                $assetRows[] = [
                    'file_name'  => $newFileName,
                    'file_type'  => $asset->file_type,
                    'extension'  => $asset->extension,
                    'file_size'  => $asset->file_size,
                    'path'       => $newStoragePath,
                    'mime_type'  => $asset->mime_type,
                    // meta_data is cast array on Asset model; DB::table()->insert() needs raw JSON.
                    'meta_data'  => $asset->meta_data !== null ? json_encode($asset->meta_data) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $assetPaths[] = $newStoragePath;
            }

            // 1 bulk INSERT instead of N Asset::create() calls.
            DB::table((new Asset)->getTable())->insert($assetRows);

            // Fetch new IDs by unique path — portable across MySQL + PostgreSQL.
            $newIds = Asset::whereIn('path', $assetPaths)->pluck('id');

            // 1 bulk pivot INSERT instead of N attach() calls.
            DB::table('dam_asset_directory')->insert(
                $newIds->map(fn ($id) => ['asset_id' => $id, 'directory_id' => $newParent->id])->all()
            );
        }

        foreach ($source->children as $child) {
            $newChild = Directory::create(['name' => $child->name, 'parent_id' => $newParent->id]);
            $this->deepCopyDirectory($child, $newChild, $depth + 1);
        }
    }

    /**
     * Resolve a unique file name against an in-memory name set — no DB queries.
     * Caller must register the returned name into $existingNames after use.
     *
     * @param  array<string, mixed>  $existingNames  Flipped pluck — keys are names, used as a hash set.
     */
    private function uniqueAssetNameFromSet(string $fileName, array $existingNames): string
    {
        if (! isset($existingNames[$fileName])) {
            return $fileName;
        }

        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $base = $ext ? substr($fileName, 0, -(strlen($ext) + 1)) : $fileName;
        $dotExt = $ext ? '.'.$ext : '';

        $candidate = $base.' (copy)'.$dotExt;
        if (! isset($existingNames[$candidate])) {
            return $candidate;
        }

        $i = 1;
        do {
            $candidate = $base.' (copy) ('.$i.')'.$dotExt;
            $i++;
        } while (isset($existingNames[$candidate]));

        return $candidate;
    }
}
