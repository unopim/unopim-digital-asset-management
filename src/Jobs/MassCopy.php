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

    private const MAX_DEPTH = 100;

    /** Per-directory name cache: [dirId => [name => true]] — 1 query per dir, then O(1) lookups */
    private array $usedNames = [];

    public function __construct(
        protected array $assetIds,
        protected array $dirIds,
        protected int $targetId,
        protected int $userId
    ) {}

    public function handle(): void
    {
        if (! $this->checkedUser($this->userId)) {
            $this->failed(EventType::MASS_COPY->value, $this->userId, 'User not found');

            return;
        }

        try {
            $disk = Directory::getAssetDisk();
            $targetDirectory = Directory::findOrFail($this->targetId);
            $targetDirPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $targetDirectory->generatePath());

            // ── Assets ──────────────────────────────────────────────────────────
            if (! empty($this->assetIds)) {
                // 1 query instead of N
                $assets = Asset::whereIn('id', $this->assetIds)->get();

                Storage::disk($disk)->makeDirectory($targetDirPath); // once, outside loop

                $newAssetIds = [];

                foreach ($assets as $asset) {
                    if (! $asset->path) {
                        continue;
                    }

                    $ext = $asset->extension ? '.'.$asset->extension : '';
                    $base = pathinfo($asset->file_name, PATHINFO_FILENAME);
                    $newName = $this->resolveUniqueName($base, $ext, $this->targetId);

                    try {
                        // Skip exists() check (1 S3 call saved per asset) — catch if source missing
                        Storage::disk($disk)->copy($asset->path, $targetDirPath.'/'.$newName);
                    } catch (\Throwable $e) {
                        report($e);

                        continue;
                    }

                    $newAsset = Asset::create([
                        'file_name' => $newName,
                        'file_type' => $asset->file_type,
                        'extension' => $asset->extension,
                        'file_size' => $asset->file_size,
                        'path'      => $targetDirPath.'/'.$newName,
                        'mime_type' => $asset->mime_type,
                        'meta_data' => $asset->meta_data,
                    ]);

                    $newAssetIds[] = $newAsset->id;
                }

                // 1 pivot insert for all copied assets instead of N
                if ($newAssetIds) {
                    $targetDirectory->assets()->attach($newAssetIds);
                }
            }

            // ── Directories ─────────────────────────────────────────────────────
            foreach ($this->dirIds as $id) {
                // Eagerly load 2 levels deep to reduce recursive queries
                $source = Directory::with(['assets', 'children.assets', 'children.children'])->find($id);

                if (! $source || ! $source->isCopyable()) {
                    continue;
                }

                $newName = Directory::uniqueName($source->name, $this->targetId);

                DB::transaction(function () use ($source, $newName, $targetDirPath) {
                    $newRoot = Directory::create(['name' => $newName, 'parent_id' => $this->targetId]);
                    $newRootStoragePath = $targetDirPath.'/'.$newName;
                    $this->deepCopy($source, $newRoot, $newRootStoragePath, 0);
                });
            }

            $this->completed(EventType::MASS_COPY->value, $this->userId);
        } catch (\Throwable $e) {
            $this->failed(EventType::MASS_COPY->value, $this->userId, $e->getMessage());
        }
    }

    private function deepCopy(Directory $source, Directory $newParent, string $newParentStoragePath, int $depth): void
    {
        if ($depth > self::MAX_DEPTH) {
            throw new \RuntimeException('Directory tree exceeds maximum copy depth of '.self::MAX_DEPTH);
        }

        $source->loadMissing(['assets', 'children']);

        $disk = Directory::getAssetDisk();
        Storage::disk($disk)->makeDirectory($newParentStoragePath);

        // Copy assets for this dir; batch pivot attach at the end (1 query per dir instead of N)
        $newAssetIds = [];

        foreach ($source->assets as $asset) {
            $ext = $asset->extension ? '.'.$asset->extension : '';
            $base = pathinfo($asset->file_name, PATHINFO_FILENAME);
            $newName = $this->resolveUniqueName($base, $ext, $newParent->id);

            try {
                Storage::disk($disk)->copy($asset->path, $newParentStoragePath.'/'.$newName);
            } catch (\Throwable $e) {
                report($e);

                continue;
            }

            $newAsset = Asset::create([
                'file_name' => $newName,
                'file_type' => $asset->file_type,
                'extension' => $asset->extension,
                'file_size' => $asset->file_size,
                'path'      => $newParentStoragePath.'/'.$newName,
                'mime_type' => $asset->mime_type,
                'meta_data' => $asset->meta_data,
            ]);

            $newAssetIds[] = $newAsset->id;
        }

        if ($newAssetIds) {
            $newParent->assets()->attach($newAssetIds);
        }

        foreach ($source->children as $child) {
            $child->loadMissing(['assets', 'children']);
            $newChild = Directory::create(['name' => $child->name, 'parent_id' => $newParent->id]);
            $newChildStoragePath = $newParentStoragePath.'/'.$newChild->name;
            // Pass path down — avoids generatePath() DB query (ancestor lookup) per level
            $this->deepCopy($child, $newChild, $newChildStoragePath, $depth + 1);
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
}
