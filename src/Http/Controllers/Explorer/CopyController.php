<?php

declare(strict_types=1);

namespace Webkul\DAM\Http\Controllers\Explorer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Enums\EventType;
use Webkul\DAM\Jobs\CopyDirectory as CopyDirectoryJob;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\DAM\Traits\ActionRequest as ActionRequestTrait;

class CopyController extends Controller
{
    use ActionRequestTrait;

    public function __construct(
        protected DirectoryPermissionService $permissionService
    ) {}

    public function copyAsset(Request $request): JsonResponse
    {
        $request->validate([
            'asset_id'            => 'required|integer|exists:dam_assets,id',
            'target_directory_id' => 'required|integer|exists:dam_directories,id',
        ]);

        $asset = Asset::findOrFail($request->integer('asset_id'));
        $targetId = $request->integer('target_directory_id');

        if (! $this->permissionService->bypass() && ! $this->permissionService->canView($targetId)) {
            return response()->json(['message' => trans('dam::app.admin.explorer.access-denied')], 403);
        }

        $sourceDir = $asset->directories()->first();
        if ($sourceDir && ! $this->permissionService->bypass() && ! $this->permissionService->canView($sourceDir->id)) {
            return response()->json(['message' => trans('dam::app.admin.explorer.access-denied')], 403);
        }

        $disk = Directory::getAssetDisk();
        $baseName = pathinfo($asset->file_name, PATHINFO_FILENAME);
        $ext = $asset->extension ? '.'.$asset->extension : '';
        $newName = $this->uniqueAssetName($baseName, $ext, $targetId);

        $targetDirectory = Directory::findOrFail($targetId);
        $targetDirStoragePath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $targetDirectory->generatePath());
        Storage::disk($disk)->makeDirectory($targetDirStoragePath);
        $newStoragePath = $targetDirStoragePath.'/'.$newName;
        Storage::disk($disk)->copy($asset->path, $newStoragePath);

        $newAsset = Asset::create([
            'file_name' => $newName,
            'file_type' => $asset->file_type,
            'extension' => $asset->extension,
            'file_size' => $asset->file_size,
            'path'      => $newStoragePath,
            'mime_type' => $asset->mime_type,
            'meta_data' => $asset->meta_data,
        ]);

        $newAsset->directories()->attach($targetId);

        return response()->json([
            'message' => trans('dam::app.admin.explorer.context.copy-done'),
            'asset'   => $newAsset,
        ], 201);
    }

    public function copyDirectory(Request $request): JsonResponse
    {
        $request->validate([
            'directory_id'        => 'required|integer|exists:dam_directories,id',
            'target_directory_id' => 'required|integer|exists:dam_directories,id',
        ]);

        $sourceId = $request->integer('directory_id');
        $targetId = $request->integer('target_directory_id');

        if (! $this->permissionService->bypass() && ! $this->permissionService->canView($targetId)) {
            return response()->json(['message' => trans('dam::app.admin.explorer.access-denied')], 403);
        }

        if (! $this->permissionService->bypass() && ! $this->permissionService->canView($sourceId)) {
            return response()->json(['message' => trans('dam::app.admin.explorer.access-denied')], 403);
        }

        $requestAction = $this->start(EventType::COPY_DIRECTORY->value);

        CopyDirectoryJob::dispatch($sourceId, $targetId, $requestAction->getUser()->id);

        return response()->json([
            'message' => trans('dam::app.admin.explorer.context.copy-progress'),
            'name'    => Directory::findOrFail($sourceId)->name,
        ], 202);
    }

    public function copyStructureTo(Request $request): JsonResponse
    {
        $request->validate([
            'source_id' => 'required|integer|exists:dam_directories,id',
            'target_id' => 'required|integer|exists:dam_directories,id',
        ]);

        if (! $this->permissionService->bypass() && ! $this->permissionService->canView($request->integer('target_id'))) {
            return response()->json(['message' => trans('dam::app.admin.explorer.access-denied')], 403);
        }

        if (! $this->permissionService->bypass() && ! $this->permissionService->canView($request->integer('source_id'))) {
            return response()->json(['message' => trans('dam::app.admin.explorer.access-denied')], 403);
        }

        $source = Directory::findOrFail($request->integer('source_id'));
        $target = Directory::findOrFail($request->integer('target_id'));
        $newName = $this->uniqueDirName($source->name, $target->id);

        $newRoot = Directory::create(['name' => $newName, 'parent_id' => $target->id]);
        $this->deepCopyStructure($source, $newRoot);

        return response()->json([
            'message' => trans('dam::app.admin.explorer.context.copy-done'),
        ], 202);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function uniqueAssetName(string $base, string $ext, int $dirId): string
    {
        $candidate = $base.$ext;
        if (! $this->assetNameExists($candidate, $dirId)) {
            return $candidate;
        }

        $candidate = $base.' (copy)'.$ext;
        if (! $this->assetNameExists($candidate, $dirId)) {
            return $candidate;
        }

        $i = 1;
        do {
            $candidate = $base.' (copy) ('.$i.')'.$ext;
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

    private function uniqueDirName(string $name, int $parentId): string
    {
        $candidate = $name;
        if (! $this->dirNameExists($candidate, $parentId)) {
            return $candidate;
        }

        $candidate = $name.' (copy)';
        if (! $this->dirNameExists($candidate, $parentId)) {
            return $candidate;
        }

        $i = 1;
        do {
            $candidate = $name.' (copy) ('.$i.')';
            $i++;
        } while ($this->dirNameExists($candidate, $parentId));

        return $candidate;
    }

    private function dirNameExists(string $name, int $parentId): bool
    {
        return Directory::where('name', $name)->where('parent_id', $parentId)->exists();
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

    private function deepCopyStructure(Directory $source, Directory $newParent): void
    {
        $source->loadMissing('children');

        foreach ($source->children as $child) {
            $newChild = Directory::create(['name' => $child->name, 'parent_id' => $newParent->id]);
            $this->deepCopyStructure($child, $newChild);
        }
    }
}
