<?php

declare(strict_types=1);

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Enums\EventType;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Traits\ActionRequest as ActionRequestTrait;

class CopyDirectory
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
            $this->failed(EventType::COPY_DIRECTORY->value, $this->userId, 'User not found');

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
            $this->failed(EventType::COPY_DIRECTORY->value, $this->userId, $e->getMessage());
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

        foreach ($source->assets as $asset) {
            $newFileName = $this->uniqueAssetName($asset->file_name, $newParent->id);
            $newStoragePath = $newParentStoragePath.'/'.$newFileName;
            Storage::disk($disk)->copy($asset->path, $newStoragePath);

            $newAsset = Asset::create([
                'file_name' => $newFileName,
                'file_type' => $asset->file_type,
                'extension' => $asset->extension,
                'file_size' => $asset->file_size,
                'path'      => $newStoragePath,
                'mime_type' => $asset->mime_type,
                'meta_data' => $asset->meta_data,
            ]);

            $newAsset->directories()->attach($newParent->id);
        }

        foreach ($source->children as $child) {
            $newChild = Directory::create(['name' => $child->name, 'parent_id' => $newParent->id]);
            $this->deepCopyDirectory($child, $newChild, $depth + 1);
        }
    }

    private function uniqueAssetName(string $fileName, int $dirId): string
    {
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $base = $ext ? substr($fileName, 0, -(strlen($ext) + 1)) : $fileName;
        $dotExt = $ext ? '.'.$ext : '';

        if (! $this->assetNameExists($fileName, $dirId)) {
            return $fileName;
        }

        $candidate = $base.' (copy)'.$dotExt;
        if (! $this->assetNameExists($candidate, $dirId)) {
            return $candidate;
        }

        $i = 1;
        do {
            $candidate = $base.' (copy) ('.$i.')'.$dotExt;
            $i++;
        } while ($this->assetNameExists($candidate, $dirId));

        return $candidate;
    }

    private function assetNameExists(string $name, int $dirId): bool
    {
        return Asset::where('file_name', $name)
            ->whereHas('directories', fn ($q) => $q->where('dam_directories.id', $dirId))
            ->exists();
    }
}
