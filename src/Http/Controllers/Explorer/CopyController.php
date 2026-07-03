<?php

declare(strict_types=1);

namespace Webkul\DAM\Http\Controllers\Explorer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Enums\EventType;
use Webkul\DAM\Jobs\CopyDirectory as CopyDirectoryJob;
use Webkul\DAM\Jobs\MassCopy as MassCopyJob;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\DAM\Traits\ActionRequest as ActionRequestTrait;
use Webkul\DAM\Traits\AssetAccessControl;

class CopyController extends Controller
{
    use ActionRequestTrait, AssetAccessControl;

    /** Create a new instance. */
    public function __construct(
        protected DirectoryPermissionService $permissionService
    ) {}

    /**
     * Copy a single asset into the target directory with a unique name.
     */
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

    /**
     * Validate access and queue a job to copy a directory into the target.
     */
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

        $source = Directory::findOrFail($sourceId);

        $requestAction = $this->start(EventType::COPY_DIRECTORY->value);

        CopyDirectoryJob::dispatch($sourceId, $targetId, $requestAction->getUser()->id);

        return response()->json([
            'message' => trans('dam::app.admin.explorer.context.copy-progress'),
            'name'    => $source->name,
        ], 202);
    }

    /**
     * Recursively copy a directory's folder structure into the target.
     */
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
        $newName = Directory::uniqueName($source->name, $target->id);

        $newRoot = Directory::create(['name' => $newName, 'parent_id' => $target->id]);
        $this->deepCopyStructure($source, $newRoot);

        return response()->json([
            'message' => trans('dam::app.admin.explorer.context.copy-done'),
        ], 202);
    }

    /**
     * Validate access and queue a job to copy the selected assets and directories.
     */
    public function massCopy(Request $request): JsonResponse
    {
        $request->validate([
            'asset_ids'           => 'nullable|array',
            'asset_ids.*'         => 'integer',
            'directory_ids'       => 'nullable|array',
            'directory_ids.*'     => 'integer',
            'target_directory_id' => 'required|integer|exists:dam_directories,id',
        ]);

        $targetId = $request->integer('target_directory_id');

        if (! $this->permissionService->bypass() && ! $this->permissionService->canView($targetId)) {
            return response()->json(['message' => trans('dam::app.admin.explorer.access-denied')], 403);
        }

        $disk = Directory::getAssetDisk();

        $assetIds = array_filter(
            array_map('intval', $request->input('asset_ids', [])),
            function (int $id) use ($disk) {
                if (! $this->damCanAccessAsset($id)) {
                    return false;
                }

                $asset = Asset::find($id);

                if (! $asset || ! $asset->path || ! Storage::disk($disk)->exists($asset->path)) {
                    return false;
                }

                $sourceDir = $asset->directories()->first();

                if ($sourceDir && ! $this->permissionService->bypass() && ! $this->permissionService->canView($sourceDir->id)) {
                    return false;
                }

                return true;
            }
        );

        $dirIds = array_filter(
            array_map('intval', $request->input('directory_ids', [])),
            function (int $id) {
                if (! $this->permissionService->bypass() && ! $this->permissionService->canView($id)) {
                    return false;
                }

                $dir = Directory::find($id);

                return $dir && $dir->isCopyable();
            }
        );

        $requestAction = $this->start(EventType::MASS_COPY->value);

        MassCopyJob::dispatch(
            array_values($assetIds),
            array_values($dirIds),
            $targetId,
            $requestAction->getUser()->id
        );

        return response()->json(['queued' => true]);
    }

    /**
     * Build a filename for the copy that does not collide within the directory.
     */
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

    /**
     * Determine whether an asset with the given name already exists in the directory.
     */
    private function assetNameExists(string $name, int $dirId): bool
    {
        return Asset::where('file_name', $name)
            ->whereHas('directories', fn ($q) => $q->where('dam_directories.id', $dirId))
            ->exists();
    }

    /**
     * Recursively recreate the source directory's child folders under the new parent.
     */
    private function deepCopyStructure(Directory $source, Directory $newParent): void
    {
        $source->loadMissing('children');

        foreach ($source->children as $child) {
            $newChild = Directory::create(['name' => $child->name, 'parent_id' => $newParent->id]);
            $this->deepCopyStructure($child, $newChild);
        }
    }
}
