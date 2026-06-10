<?php

declare(strict_types=1);

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Enums\EventType;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Traits\ActionRequest as ActionRequestTrait;

class CopyDirectory
{
    use ActionRequestTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $sourceId,
        protected int $targetId,
        protected int $userId
    ) {}

    public function handle(): void
    {
        if (! $this->checkedUser($this->userId)) {
            throw new \Exception('User not found');
        }

        $source = Directory::with(['assets', 'children'])->findOrFail($this->sourceId);
        $newName = $this->uniqueDirName($source->name, $this->targetId);

        $newRoot = Directory::create(['name' => $newName, 'parent_id' => $this->targetId]);
        $this->deepCopyDirectory($source, $newRoot);

        $this->completed(EventType::COPY_DIRECTORY->value, $this->userId);
    }

    private function deepCopyDirectory(Directory $source, Directory $newParent): void
    {
        $source->loadMissing(['assets', 'children']);

        $disk = Directory::getAssetDisk();
        $newParentStoragePath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $newParent->generatePath());
        Storage::disk($disk)->makeDirectory($newParentStoragePath);

        foreach ($source->assets as $asset) {
            $newStoragePath = $newParentStoragePath.'/'.$asset->file_name;
            Storage::disk($disk)->copy($asset->path, $newStoragePath);

            $newAsset = Asset::create([
                'file_name' => $asset->file_name,
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
            $this->deepCopyDirectory($child, $newChild);
        }
    }

    private function uniqueDirName(string $name, int $parentId): string
    {
        $candidate = $name;
        if (! Directory::where('name', $candidate)->where('parent_id', $parentId)->exists()) {
            return $candidate;
        }

        $candidate = $name.' (copy)';
        if (! Directory::where('name', $candidate)->where('parent_id', $parentId)->exists()) {
            return $candidate;
        }

        $i = 1;
        do {
            $candidate = $name.' (copy) ('.$i.')';
            $i++;
        } while (Directory::where('name', $candidate)->where('parent_id', $parentId)->exists());

        return $candidate;
    }
}
