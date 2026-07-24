<?php

namespace Webkul\DAM\Http\Controllers\Asset;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\DAM\DataGrids\Asset\AssetDataGrid;
use Webkul\DAM\Filesystem\FileStorer;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Jobs\GeneratePdfThumbnail;
use Webkul\DAM\Jobs\GenerateVideoThumbnail;
use Webkul\DAM\Jobs\ProcessAssetUpload;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetComments;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\UploadBatch;
use Webkul\DAM\Models\UploadTracker;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Repositories\AssetTagRepository;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Repositories\DirectoryRolePermissionRepository;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\DAM\Services\MetadataExtractionService;
use Webkul\DAM\Traits\Directory as DirectoryTrait;
use ZipArchive;

class AssetController extends Controller
{
    use DirectoryTrait;

    public function __construct(
        protected AssetRepository $assetRepository,
        protected AssetTagRepository $assetTagRepository,
        protected FileStorer $fileStorer,
        protected DirectoryRepository $directoryRepository,
        protected MetadataExtractionService $metadataExtractionService,
        protected DirectoryPermissionService $permissionService,
        protected DirectoryRolePermissionRepository $permissionRepository,
    ) {}

    protected function assetDirectoryId(?Asset $asset): ?int
    {
        if (! $asset) {
            return null;
        }

        $dirId = $asset->directories()->value('dam_directories.id');

        return $dirId ? (int) $dirId : null;
    }

    protected function canActOnAsset(?Asset $asset): bool
    {
        if ($this->permissionService->bypass()) {
            return true;
        }

        $dirId = $this->assetDirectoryId($asset);

        if ($dirId === null) {
            return false;
        }

        return $this->permissionService->canAccess($dirId);
    }

    public function index()
    {
        if (request()->ajax()) {
            return app(AssetDataGrid::class)->toJson();
        }

        return view('dam::asset.index');
    }

    public function edit(int $id)
    {
        $asset = $this->assetRepository->find($id);

        if (! $asset) {
            abort(404);
        }

        abort_unless($this->canActOnAsset($asset), 403, trans('dam::app.admin.permissions.unauthorized'));

        $asset->previewPath = AssetHelper::getPreviewUrl(
            $asset->path,
            1356
        );

        $asset->width = '';
        $asset->height = '';

        if ($asset->file_type === 'image') {
            $metaData = is_array($asset->meta_data) ? $asset->meta_data : [];

            $asset->width = $metaData['exif']['Width'] ?? $metaData['exif']['COMPUTED']['Width'] ?? '';
            $asset->height = $metaData['exif']['Height'] ?? $metaData['exif']['COMPUTED']['Height'] ?? '';
        }

        $asset->comments = $asset->comments()->orderBy('created_at', 'desc')->get();

        $tags = $this->assetTagRepository->all();

        $directory = $asset->directories()->first();
        $directoryAncestors = $directory
            ? $directory->ancestorsAndSelfAndDefaultOrder($directory->id)
            : collect();

        $asset = $this->getNextAndPreviousAssets($asset, $id, $directory);

        $assetModel = $this->assetRepository->model();
        $scopedQuery = $directory
            ? $assetModel::whereHas('directories', fn ($q) => $q->where('dam_directories.id', $directory->id))
            : $assetModel::whereDoesntHave('directories');

        $assetPosition = (clone $scopedQuery)->where('id', '<=', $id)->count();
        $assetTotal = (clone $scopedQuery)->count();

        $bytes = (int) ($asset->file_size ?? 0);
        $fileSize = $bytes >= 1048576
            ? number_format($bytes / 1048576, 2).' MB'
            : ($bytes >= 1024 ? number_format($bytes / 1024, 1).' KB' : ($bytes > 0 ? $bytes.' B' : null));

        return view('dam::asset.edit', compact('asset', 'id', 'tags', 'assetPosition', 'assetTotal', 'fileSize', 'directoryAncestors', 'directory'));
    }

    protected function getNextAndPreviousAssets($asset, $id, ?Directory $directory = null)
    {
        $assetModel = $this->assetRepository->model();
        $dir = $directory ?? $asset->directories()->first();

        $scopedQuery = $dir
            ? $assetModel::whereHas('directories', fn ($q) => $q->where('dam_directories.id', $dir->id))
            : $assetModel::whereDoesntHave('directories');

        $asset->nextAssetId = (clone $scopedQuery)->where('id', '>', $id)->orderBy('id', 'asc')->value('id');
        $asset->previousAssetId = (clone $scopedQuery)->where('id', '<', $id)->orderBy('id', 'desc')->value('id');

        return $asset;
    }

    public function getMetadataById($id)
    {
        try {
            $asset = $this->assetRepository->find($id);
            $metaData = [];

            if ($asset->meta_data) {
                $metaData = is_array($asset->meta_data)
                    ? $asset->meta_data
                    : json_decode($asset->meta_data, true);

                $metaData = $this->flattenExifMetadata($metaData);
            } else {
                $disk = Directory::getAssetDisk();

                if (! Storage::disk($disk)->exists($asset->path)) {
                    throw new \Exception(trans('dam::app.admin.dam.asset.edit.image-source-not-readable'));
                }

                $metaData = $this->flattenExifMetadata(
                    $this->metadataExtractionService->extractMetadata($asset->path, $disk)
                );

                if ($asset->file_type === 'image') {
                    unset($metaData['UndefinedTag:0xEA1C']);
                }
            }

            return response()->json([
                'success' => true,
                'data'    => $metaData,
            ]);
        } catch (\Exception $e) {
            report($e);

            return [
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.edit.failed-to-read', ['exception' => $e->getMessage()]),
            ];
        }
    }

    private function flattenExifMetadata(array $metaData): array
    {
        if (! isset($metaData['exif']) || ! is_array($metaData['exif'])) {
            return $metaData;
        }

        $flatExif = collect($metaData['exif'])
            ->partition(fn ($v) => ! is_array($v))
            ->pipe(function ($parts) {
                return $parts[0]->all() + $parts[1]->all();
            });

        unset($metaData['exif']);

        return array_merge($flatExif, $metaData);
    }

    public function upload(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $maxKb = AssetHelper::getMaxUploadSizeKb();
        $sizeMessage = trans('dam::app.admin.dam.asset.datagrid.file-too-large', [
            'size' => $this->humanReadableSize($maxKb),
        ]);

        $request->validate([
            'files'        => 'required|array',
            'files.*'      => 'file|max:'.$maxKb,
            'directory_id' => 'required|exists:dam_directories,id',
        ], [
            'files.*.max'      => $sizeMessage,
            'files.*.uploaded' => $sizeMessage,
            'files.*.file'     => $sizeMessage,
        ]);

        $files = $request->file('files');
        $directoryId = $request->get('directory_id');

        if (! $this->permissionService->canAccess((int) $directoryId)) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        $directory = $this->directoryRepository->find($directoryId);
        $directoryPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $directory->generatePath());

        $uploadFiles = [];
        $assetIds = [];
        $disk = Directory::getAssetDisk();
        $tracker = $this->resolveUploadTracker($request, (int) $directoryId);

        if (! $directory->isWritable($directoryPath)) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.index.directory.not-writable', [
                    'type'       => 'file',
                    'actionType' => 'create',
                    'path'       => $directoryPath,
                ]),
            ], 500);
        }

        $candidatePaths = [];
        foreach ($files as $f) {
            if ($f instanceof UploadedFile) {
                $candidatePaths[] = $directoryPath.'/'.$f->getClientOriginalName();
            }
        }
        $existingByPath = $candidatePaths
            ? Asset::whereIn('path', $candidatePaths)->get()->keyBy('path')
            : collect();

        try {
            foreach ($files as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $extension = strtolower($file->getClientOriginalExtension());
                $mimeType = $file->getMimeType();

                if (AssetHelper::isForbiddenFile($extension, $mimeType, $file->getClientOriginalName(), $file->getRealPath())) {
                    throw new \Exception(trans('dam::app.admin.dam.index.directory.not-allowed'));
                }

                $originalName = $file->getClientOriginalName();
                $existingPath = $directoryPath.'/'.$originalName;
                $existingAsset = $existingByPath->get($existingPath);
                $isOverwrite = (bool) $existingAsset;

                if ($isOverwrite) {
                    Storage::disk($disk)->delete($existingAsset->path);
                    $this->clearAssetCache($existingAsset->path, $disk, $existingAsset->id);
                    $fileName = $originalName;
                } else {
                    $fileName = $this->generateUniqueFileName($directoryPath, $originalName);
                }

                $filePath = $this->fileStorer->store(
                    path: $directoryPath,
                    file: $file,
                    fileName: $fileName,
                    options: [FileStorer::HASHED_FOLDER_NAME_KEY => false, 'disk' => $disk]
                );

                $payload = [
                    'file_name' => $fileName,
                    'file_type' => AssetHelper::getFileType($file),
                    'file_size' => $file->getSize(),
                    'mime_type' => $mimeType,
                    'extension' => $file->getClientOriginalExtension(),
                    'path'      => $filePath,
                ];

                if ($isOverwrite) {
                    $existingAsset->update($payload);
                    $asset = $existingAsset;
                } else {
                    $asset = Asset::create($payload);
                    $assetIds[] = $asset->id;
                }

                $this->queueAssetFinalisation($asset, $tracker);

                $uploadFiles[] = $asset;
            }

            $this->mappedWithDirectory($assetIds, $directoryId);

            return response()->json([
                'success' => true,
                'files'   => $uploadFiles,
                'message' => count($uploadFiles) > 1
                    ? trans('dam::app.admin.dam.asset.datagrid.files-upload-success')
                    : trans('dam::app.admin.dam.asset.datagrid.file-upload-success'),
            ], 201);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.files-upload-failed'),
            ], 500);
        }
    }

    private function attachAudioCoverArt(Asset $asset, ?string $localFilePath, ?string $mimeType, array $metaData, string $disk): void
    {
        if (! str_starts_with($mimeType ?? '', 'audio/')) {
            return;
        }

        if (! $localFilePath || ! file_exists($localFilePath)) {
            return;
        }

        $coverData = $this->metadataExtractionService->extractCoverArtData($localFilePath);
        if (! $coverData) {
            return;
        }

        $coverPath = $this->metadataExtractionService->storeCoverArt($coverData, $asset->id, $disk);
        if (! $coverPath) {
            return;
        }

        $asset->update(['meta_data' => array_merge($metaData, ['cover_art_path' => $coverPath])]);
    }

    public function uploadFolder(Request $request): JsonResponse
    {
        abort_unless(
            bouncer()->hasPermission('dam.asset.upload'),
            403,
            trans('dam::app.admin.permissions.unauthorized')
        );

        set_time_limit(0);
        ignore_user_abort(true);

        $maxKb = AssetHelper::getMaxUploadSizeKb();
        $sizeMessage = trans('dam::app.admin.dam.asset.datagrid.file-too-large', [
            'size' => $this->humanReadableSize($maxKb),
        ]);

        $request->validate([
            'files'           => 'required|array|min:1',
            'files.*'         => 'file|max:'.$maxKb,
            'relative_paths'  => 'required|array|min:1',
            'relative_paths.*'=> 'string|max:500',
            'directory_id'    => 'required|exists:dam_directories,id',
        ], [
            'files.*.max'      => $sizeMessage,
            'files.*.uploaded' => $sizeMessage,
            'files.*.file'     => $sizeMessage,
        ]);

        $files = $request->file('files');
        $relativePaths = $request->input('relative_paths', []);
        $directoryId = (int) $request->input('directory_id');
        $preserveRoot = (bool) $request->input('preserve_root', false);

        if (! $this->permissionService->canAccess($directoryId)) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        $disk = Directory::getAssetDisk();
        $uploadedAssets = [];
        $skippedCount = 0;
        $tracker = $this->resolveUploadTracker($request, $directoryId);

        $dirCache = [];

        $createdIds = [];

        $resolveOrCreatePath = function (int $rootId, array $segments) use (&$resolveOrCreatePath, &$dirCache, &$createdIds): int {
            if (empty($segments)) {
                return $rootId;
            }

            $segment = array_shift($segments);
            $cacheKey = $rootId.'/'.$segment;

            if (! isset($dirCache[$cacheKey])) {
                $existing = Directory::where('parent_id', $rootId)
                    ->where('name', $segment)
                    ->first();

                if ($existing) {
                    $dirCache[$cacheKey] = $existing->id;
                } else {
                    $newDir = $this->directoryRepository->create([
                        'name'      => $segment,
                        'parent_id' => $rootId,
                    ]);
                    $dirCache[$cacheKey] = $newDir->id;
                    $createdIds[] = $newDir->id;
                }
            }

            return $resolveOrCreatePath($dirCache[$cacheKey], $segments);
        };

        $assetsByDir = [];

        try {
            $fileJobs = [];

            foreach ($files as $index => $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $relativePath = $relativePaths[$index] ?? $file->getClientOriginalName();

                $parts = explode('/', ltrim(str_replace('\\', '/', $relativePath), '/'));
                array_pop($parts);
                if (! $preserveRoot && ! empty($parts)) {
                    array_shift($parts);
                }

                $hasBadSegment = false;
                $forbiddenRe = '/[\\\\\/\:\*\?\"\<\>\|]/u';

                foreach ($parts as $seg) {
                    if ($seg === '.' || $seg === '..' || mb_strlen($seg) > 255 || preg_match($forbiddenRe, $seg)) {
                        $hasBadSegment = true;

                        break;
                    }
                }

                if ($hasBadSegment) {
                    $skippedCount++;

                    continue;
                }

                $extension = strtolower($file->getClientOriginalExtension());
                $mimeType = $file->getMimeType();

                if (AssetHelper::isForbiddenFile($extension, $mimeType, $file->getClientOriginalName(), $file->getRealPath())) {
                    $skippedCount++;

                    continue;
                }

                $targetDirId = $resolveOrCreatePath($directoryId, $parts);
                $targetDirectory = $this->directoryRepository->find($targetDirId);
                $targetDirPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $targetDirectory->generatePath());

                if (! $targetDirectory->isWritable($targetDirPath)) {
                    $skippedCount++;

                    continue;
                }

                $originalName = $file->getClientOriginalName();

                $fileJobs[] = [
                    'file'            => $file,
                    'mimeType'        => $mimeType,
                    'targetDirId'     => $targetDirId,
                    'targetDirectory' => $targetDirectory,
                    'targetDirPath'   => $targetDirPath,
                    'originalName'    => $originalName,
                    'existingPath'    => $targetDirPath.'/'.$originalName,
                ];
            }

            $candidatePaths = array_column($fileJobs, 'existingPath');
            $existingByPath = $candidatePaths
                ? Asset::whereIn('path', $candidatePaths)->get()->keyBy('path')
                : collect();

            foreach ($fileJobs as $job) {
                $file = $job['file'];
                $mimeType = $job['mimeType'];
                $targetDirId = $job['targetDirId'];
                $targetDirectory = $job['targetDirectory'];
                $targetDirPath = $job['targetDirPath'];
                $originalName = $job['originalName'];
                $existingPath = $job['existingPath'];

                $existingAsset = $existingByPath->get($existingPath);
                $isOverwrite = (bool) $existingAsset;

                if ($isOverwrite) {
                    Storage::disk($disk)->delete($existingAsset->path);
                    $this->clearAssetCache($existingAsset->path, $disk, $existingAsset->id);
                    $fileName = $originalName;
                } else {
                    $fileName = $this->generateUniqueFileName($targetDirPath, $originalName);
                }

                $filePath = $this->fileStorer->store(
                    path: $targetDirPath,
                    file: $file,
                    fileName: $fileName,
                    options: [FileStorer::HASHED_FOLDER_NAME_KEY => false, 'disk' => $disk]
                );

                $payload = [
                    'file_name' => $fileName,
                    'file_type' => AssetHelper::getFileType($file),
                    'file_size' => $file->getSize(),
                    'mime_type' => $mimeType,
                    'extension' => $file->getClientOriginalExtension(),
                    'path'      => $filePath,
                ];

                if ($isOverwrite) {
                    $existingAsset->update($payload);
                    $asset = $existingAsset;
                } else {
                    $asset = Asset::create($payload);
                    $assetsByDir[$targetDirId][] = $asset->id;
                }

                $this->queueAssetFinalisation($asset, $tracker);

                $uploadedAssets[] = $asset;
            }

            foreach ($assetsByDir as $dirId => $ids) {
                $this->mappedWithDirectory($ids, $dirId);
            }

            foreach ($createdIds as $id) {
                $this->autoGrantToCreator($id);
            }

            $message = $skippedCount > 0
                ? trans('dam::app.admin.dam.index.directory.folder-upload-partial', ['count' => $skippedCount])
                : trans('dam::app.admin.dam.index.directory.folder-upload-success');

            return response()->json([
                'success'                => true,
                'files'                  => $uploadedAssets,
                'message'                => $message,
                'granted_directory_ids'  => $createdIds,
            ], 201);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.index.directory.creation-failed'),
            ], 500);
        }
    }

    public function reUpload(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $maxKb = AssetHelper::getMaxUploadSizeKb();
        $sizeMessage = trans('dam::app.admin.dam.asset.datagrid.file-too-large', [
            'size' => $this->humanReadableSize($maxKb),
        ]);

        $request->validate([
            'file'     => 'required|file|max:'.$maxKb,
            'asset_id' => 'required|exists:dam_assets,id',
        ], [
            'file.max'      => $sizeMessage,
            'file.uploaded' => $sizeMessage,
            'file.file'     => $sizeMessage,
        ]);

        $file = $request->file('file');
        $assetId = $request->get('asset_id');
        $asset = $this->assetRepository->find($assetId);

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found'),
            ], 404);
        }

        if (! $this->canActOnAsset($asset)) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        $directoryId = $asset?->directories()?->get()[0]?->id;
        $directory = $this->directoryRepository->find($directoryId);
        $directoryPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $directory->generatePath());

        if ($file instanceof UploadedFile) {
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();
            if (AssetHelper::isForbiddenFile($extension, $mimeType, $file->getClientOriginalName(), $file->getRealPath())) {
                return response()->json([
                    'success' => false,
                    'message' => trans('dam::app.admin.dam.index.directory.not-allowed', ['fileName' => $file->getClientOriginalName()]),
                ], 400);
            }

            $disk = Directory::getAssetDisk();
            $oldPath = $asset->path;
            Storage::disk($disk)->delete($oldPath);
            $this->clearAssetCache($oldPath, $disk, $asset->id);

            $originalName = $file->getClientOriginalName();
            $uniqueFileName = $this->generateUniqueFileName($directoryPath, $originalName);

            if (! $directory->isWritable($directoryPath)) {
                throw new \Exception(trans('dam::app.admin.dam.index.directory.not-writable', [
                    'type'       => 'file',
                    'actionType' => 'create',
                    'path'       => $directoryPath,
                ]));
            }

            $localFilePath = $file->getRealPath();
            $metaData = $this->metadataExtractionService->extractMetadata($localFilePath, disk: 'local', originalFileName: $originalName);

            if (str_starts_with($file->getMimeType() ?? '', 'audio/') && $localFilePath && file_exists($localFilePath)) {
                $coverData = $this->metadataExtractionService->extractCoverArtData($localFilePath);
                if ($coverData) {
                    $coverPath = $this->metadataExtractionService->storeCoverArt($coverData, $asset->id, $disk);
                    if ($coverPath) {
                        $metaData = array_merge($metaData, ['cover_art_path' => $coverPath]);
                    }
                }
            }

            $filePath = $this->fileStorer->store(
                path: $directoryPath,
                file: $file,
                fileName: $uniqueFileName,
                options: [FileStorer::HASHED_FOLDER_NAME_KEY => false, 'disk' => $disk]
            );

            $asset->update([
                'file_name' => $uniqueFileName,
                'file_type' => AssetHelper::getFileType($file),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'path'      => $filePath,
                'meta_data' => $metaData,
            ]);

            $this->dispatchThumbnailJob($asset->fresh());
        }

        return response()->json([
            'success' => true,
            'file'    => $asset,
            'message' => trans('dam::app.admin.dam.asset.edit.file-re-upload-success'),
        ], 201);
    }

    protected function resolveUploadTracker(Request $request, int $directoryId): ?UploadTracker
    {
        $sessionUuid = $request->input('session_uuid');

        if (! $sessionUuid) {
            return null;
        }

        $tracker = UploadTracker::where('uuid', $sessionUuid)->first();

        if (! $tracker) {
            return UploadTracker::create([
                'uuid'         => $sessionUuid,
                'user_id'      => auth()->id(),
                'directory_id' => $directoryId,
                'state'        => UploadTracker::STATE_PROCESSING,
                'total_files'  => 0,
                'started_at'   => now(),
            ]);
        }

        return $tracker->isActive() ? $tracker : null;
    }

    protected function queueAssetFinalisation(Asset $asset, ?UploadTracker $tracker): void
    {
        $batchId = null;

        if ($tracker) {
            $batchId = UploadBatch::create([
                'upload_tracker_id' => $tracker->id,
                'asset_id'          => $asset->id,
                'state'             => UploadBatch::STATE_PENDING,
            ])->id;
        }

        ProcessAssetUpload::dispatch($asset->id, $batchId);
    }

    protected function dispatchThumbnailJob(?Asset $asset): void
    {
        if (! $asset) {
            return;
        }

        if ($asset->file_type === 'video') {
            GenerateVideoThumbnail::dispatch($asset->id)->afterCommit();

            return;
        }

        if (strtolower((string) $asset->extension) === 'pdf') {
            GeneratePdfThumbnail::dispatch($asset->id)->afterCommit();
        }
    }

    public function show($id): JsonResponse
    {
        $asset = Asset::find($id);

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found-to-show'),
            ], 404);
        }

        if (! $this->canActOnAsset($asset)) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        $asset->previewPath = AssetHelper::getPreviewUrl($asset->path, 1356);

        $width = '';
        $height = '';

        if ($asset->file_type === 'image') {
            $metaData = is_array($asset->meta_data) ? $asset->meta_data : [];
            $width = $metaData['exif']['Width'] ?? $metaData['exif']['COMPUTED']['Width'] ?? '';
            $height = $metaData['exif']['Height'] ?? $metaData['exif']['COMPUTED']['Height'] ?? '';
        }

        $mediaUrl = AssetHelper::getPreviewUrl($asset->path);

        $placeholderSvg = match ($asset->file_type) {
            'video'    => asset('storage/dam/preview/video.svg'),
            'audio'    => asset('storage/dam/preview/audio.svg'),
            'document' => asset('storage/dam/preview/file.svg'),
            default    => asset('storage/dam/preview/unspecified.svg'),
        };

        $coverArtUrl = ($asset->file_type === 'audio' && ! empty($asset->meta_data['cover_art_path']))
            ? route('admin.dam.file.cover-art', $asset->id)
            : null;

        $typeColor = 'bg-violet-100 text-violet-700 dark:bg-violet-900 dark:text-violet-300';

        $bytes = (int) ($asset->file_size ?? 0);
        $fileSize = $bytes >= 1048576
            ? number_format($bytes / 1048576, 2).' MB'
            : ($bytes >= 1024 ? number_format($bytes / 1024, 1).' KB' : ($bytes > 0 ? $bytes.' B' : null));

        $assetModel = $this->assetRepository->model();
        $showDirectory = $asset->directories()->first();
        $showAncestors = $showDirectory
            ? $showDirectory->ancestorsAndSelfAndDefaultOrder($showDirectory->id)
            : collect();

        $scopedQuery = $showDirectory
            ? $assetModel::whereHas('directories', fn ($q) => $q->where('dam_directories.id', $showDirectory->id))
            : $assetModel::whereDoesntHave('directories');

        $nextAsset = (clone $scopedQuery)->where('id', '>', $id)->orderBy('id', 'asc')->first();
        $prevAsset = (clone $scopedQuery)->where('id', '<', $id)->orderBy('id', 'desc')->first();
        $assetPosition = (clone $scopedQuery)->where('id', '<=', $id)->count();
        $assetTotal = (clone $scopedQuery)->count();

        $tags = $asset->tags()->get(['dam_tags.id', 'dam_tags.name']);

        return response()->json([
            'success'               => true,
            'asset'                 => $asset,
            'assetPath'             => $asset->getPathWithOutFileSystemRoot() ?? '',
            'directoryBreadcrumb'   => $showAncestors->map(fn ($d) => ['id' => $d->id, 'name' => $d->name])->values()->toArray(),
            'mediaUrl'              => $mediaUrl,
            'previewPath'           => $asset->previewPath,
            'placeholderSvg'        => $placeholderSvg,
            'coverArtUrl'           => $coverArtUrl,
            'typeColor'             => $typeColor,
            'fileSize'              => $fileSize,
            'width'                 => $width,
            'height'                => $height,
            'createdAtFormatted'    => $asset->created_at?->format('d M Y, H:i'),
            'updatedAtFormatted'    => $asset->updated_at?->format('d M Y, H:i'),
            'downloadUrl'           => route('admin.dam.assets.download', $id),
            'downloadCompressedUrl' => route('admin.dam.assets.download_compressed', $id),
            'previousAssetId'       => $prevAsset?->id,
            'nextAssetId'           => $nextAsset?->id,
            'assetPosition'         => $assetPosition,
            'assetTotal'            => $assetTotal,
            'editUrl'               => route('admin.dam.assets.edit', $id),
            'tags'                  => $tags,
            'badgeCounts'           => [
                'properties'       => $asset->properties()->count(),
                'comments'         => AssetComments::where('dam_asset_id', $asset->id)->count(),
                'linked-resources' => $asset->resources()->count(),
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $asset = Asset::find($id);

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found-to-update'),
            ], 404);
        }

        if (! $this->canActOnAsset($asset)) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        $request->validate([
            'file_name' => 'string',
            'file_type' => 'string',
            'file_size' => 'integer',
            'mime_type' => 'string',
            'extension' => 'string',
            'path'      => 'string',
        ]);

        $asset->update($request->only(['file_name', 'file_type', 'file_size', 'mime_type', 'extension', 'path']));

        return redirect()
            ->back()
            ->with('success', trans('dam::app.admin.dam.asset.datagrid.update-success'));
    }

    public function destroy($id)
    {
        $asset = Asset::find($id);

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found-to-destroy'),
            ], 404);
        }

        if (! $this->canActOnAsset($asset)) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        if ($asset->resources()->exists()) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.delete-failed-due-to-attached-resources'),
            ], 404);
        }

        $disk = Directory::getAssetDisk();

        $fileDeleted = Storage::disk($disk)->delete($asset->path);

        if (! $fileDeleted) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.index.directory.not-writable', [
                    'type'       => 'file',
                    'actionType' => 'delete',
                    'path'       => $asset->path,
                ]),
            ], 500);
        }

        $this->clearAssetCache($asset->path, $disk);

        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.asset.delete-success'),
        ]);
    }

    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $assetIds = $massDestroyRequest->input('indices');
        $skippedAssetNames = [];

        try {
            $disk = Directory::getAssetDisk();

            $assets = Asset::whereIn('id', $assetIds)->with('resources')->get()->keyBy('id');

            $deletableIds = [];

            foreach ($assetIds as $assetId) {
                $asset = $assets->get($assetId);

                if (! $asset) {
                    continue;
                }

                if (! $this->canActOnAsset($asset)) {
                    continue;
                }

                if ($asset->resources->isNotEmpty()) {
                    $skippedAssetNames[] = $asset->file_name;

                    continue;
                }

                $fileDeleted = Storage::disk($disk)->delete($asset->path);

                if (! $fileDeleted) {
                    throw new \Exception(trans('dam::app.admin.dam.index.directory.not-writable', [
                        'type'       => 'file',
                        'actionType' => 'rename',
                        'path'       => $asset->path,
                    ]));
                }

                $this->clearAssetCache($asset->path, $disk);

                Event::dispatch('dam.asset.delete.before', $assetId);

                $deletableIds[] = $assetId;
            }

            if (! empty($deletableIds)) {
                Asset::whereIn('id', $deletableIds)->delete();

                foreach ($deletableIds as $deletedId) {
                    Event::dispatch('dam.asset.delete.after', $deletedId);
                }
            }

            if (! empty($skippedAssetNames)) {
                return new JsonResponse([
                    'message' => trans('dam::app.admin.dam.asset.delete-failed-due-to-attached-resources'),
                ], 404);
            }

            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.asset.datagrid.mass-delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);

            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.index.directory.error-operation'),
            ], 500);
        }
    }

    public function massUpdate(MassUpdateRequest $massUpdateRequest): JsonResponse
    {
        $data = $massUpdateRequest->all();

        $assetIds = $data['indices'];

        foreach ($assetIds as $assetId) {
            Event::dispatch('dam.asset.update.before', $assetId);

            Event::dispatch('dam.asset.update.after', $assetId);
        }

        return new JsonResponse([
            'message' => trans('dam::app.admin.dam.asset.datagrid.mass-update-success'),
        ]);
    }

    public function download(int $id)
    {
        $asset = Asset::find($id);
        $disk = Directory::getAssetDisk();

        if (! $asset || ! Storage::disk($disk)->exists($asset->path)) {
            abort(404);
        }

        abort_unless($this->canActOnAsset($asset), 403, trans('dam::app.admin.permissions.unauthorized'));

        if ($disk === Directory::ASSETS_DISK_AWS) {
            try {
                $url = Storage::disk($disk)->temporaryUrl(
                    $asset->path,
                    now()->addMinutes(10),
                    ['ResponseContentDisposition' => 'attachment; filename="'.$asset->file_name.'"']
                );

                return redirect()->away($url);
            } catch (\Throwable $e) {
            }
        }

        return Storage::disk($disk)->download($asset->path);
    }

    public function downloadCompressed(int $id)
    {
        $asset = Asset::find($id);
        $disk = Directory::getAssetDisk();

        if (! $asset || ! Storage::disk($disk)->exists($asset->path)) {
            abort(404);
        }

        abort_unless($this->canActOnAsset($asset), 403, trans('dam::app.admin.permissions.unauthorized'));

        $baseName = pathinfo($asset->file_name, PATHINFO_FILENAME);

        $zipPath = tempnam(sys_get_temp_dir(), 'dam_zip_');

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::OVERWRITE) !== true) {
            abort(500);
        }

        $zip->addFromString($asset->file_name, Storage::disk($disk)->get($asset->path));
        $zip->close();

        return response()->download($zipPath, $baseName.'.zip')->deleteFileAfterSend(true);
    }

    public function customDownload(Request $request, int $id)
    {
        $format = $request->query('format', null);
        $height = $request->query('height', null);
        $width = $request->query('width', null);
        $disk = Directory::getAssetDisk();

        $asset = Asset::find($id);

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found-to-download'),
            ], 404);
        }

        if (! $this->canActOnAsset($asset)) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        if ($asset->extension === 'svg' && $format) {
            if (! extension_loaded('imagick')) {
                return Storage::disk($disk)->download($asset->path, $asset->file_name);
            }

            try {
                $svgContent = Storage::disk($disk)->get($asset->path);

                $imagick = new \Imagick;
                $imagick->setBackgroundColor(new \ImagickPixel('transparent'));
                $imagick->readImageBlob($svgContent, 'image.svg');
                $imagick->setImageFormat($format);

                $fileName = pathinfo($asset->file_name, PATHINFO_FILENAME).'.'.$format;
                $tempFilePath = tempnam(sys_get_temp_dir(), 'svg_').'.'.$format;
                $imagick->writeImage($tempFilePath);
                $imagick->clear();

                return response()->download($tempFilePath, $fileName)->deleteFileAfterSend(true);
            } catch (\ImagickException $e) {
                \Log::warning('SVG conversion failed', ['asset_id' => $asset->id, 'error' => $e->getMessage()]);

                return Storage::disk($disk)->download($asset->path, $asset->file_name);
            }
        }

        if ($asset->file_type === 'image' && ($format || $height || $width)) {
            try {
                $disk = Directory::getAssetDisk();
                $fileContent = Storage::disk($disk)->get($asset->path);
                $image = (new ImageManager(new Driver))->decode($fileContent);

                if ($width || $height) {
                    $image->scale($width ? (int) $width : null, $height ? (int) $height : null);
                }

                if ($format) {
                    $fileName = pathinfo($asset->file_name, PATHINFO_FILENAME).'.'.strtolower($format);
                } else {
                    $fileName = $asset->file_name;
                }

                $tempFilePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$fileName;
                $image->save($tempFilePath);

                return response()->download($tempFilePath, $fileName)->deleteFileAfterSend(true);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => trans('dam::app.admin.dam.asset.edit.image-processing-failed', ['message' => $e->getMessage()]),
                ], 500);
            }
        }

        return Storage::disk($disk)->download($asset->path);
    }

    public function rename(Request $request): JsonResponse
    {
        $request->validate([
            'file_name' => 'required|string|max:255|regex:/^(?!\.)[\w .-]+$/',
            'id'        => 'required|exists:dam_assets,id',
        ]);

        $id = $request->input('id');
        $asset = Asset::find($id);

        if (! $asset) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.index.directory.asset-not-found'),
            ], 404);
        }

        if (! $this->canActOnAsset($asset)) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        try {
            $name = $request->input('file_name');
            $oldPath = $asset->path;
            $newPath = str_replace($asset->file_name, $name, $oldPath);

            if ($newPath !== $oldPath) {
                $disk = Directory::getAssetDisk();
                if (Storage::disk($disk)->exists($newPath)) {
                    return new JsonResponse([
                        'message' => trans('dam::app.admin.dam.index.directory.asset-name-conflict-in-the-same-directory'),
                    ], 404);
                } else {
                    if (Asset::where('path', $newPath)->exists()) {
                        $conflictingAsset = Asset::where('path', $newPath)->first();

                        return new JsonResponse([
                            'message' => trans('dam::app.admin.dam.index.directory.asset-name-already-exist', ['asset_name' => $conflictingAsset->file_name]),
                        ], 404);
                    }
                }

                if (Storage::disk($disk)->exists($oldPath)) {
                    $fileRenamed = Storage::disk($disk)->move($oldPath, $newPath);

                    if (! $fileRenamed) {
                        throw new \Exception(trans('dam::app.admin.dam.index.directory.not-writable', [
                            'type'       => 'file',
                            'actionType' => 'rename',
                            'path'       => $newPath,
                        ]));
                    }

                    $asset->update([
                        'file_name' => $name,
                        'path'      => $newPath,
                    ]);

                    return new JsonResponse([
                        'data'    => $asset,
                        'message' => trans('dam::app.admin.dam.index.directory.asset-renamed-success'),
                    ]);
                } else {
                    return new JsonResponse([
                        'message' => trans('dam::app.admin.dam.index.directory.old-file-not-found', ['old_path' => $oldPath]),
                    ], 404);
                }
            } else {
                return new JsonResponse([
                    'message' => trans('dam::app.admin.dam.index.directory.image-name-is-the-same'),
                ], 404);
            }
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'message' => trans('dam::app.admin.dam.index.directory.error-operation'),
            ], 500);
        }
    }

    public function moved(Request $request): JsonResponse
    {
        $id = $request->input('move_item_id');
        $asset = Asset::find($id);

        if (! $asset || ! $this->canActOnAsset($asset)) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        $newParentId = (int) $request->input('new_parent_id');
        if (! $this->permissionService->canAccess($newParentId)) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        $oldDirectory = $asset->directories()->first();
        $oldPath = sprintf('%s/%s', $oldDirectory->generatePath(), $asset->file_name);

        $directory = $this->directoryRepository->find($newParentId);

        $directoryPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $directory->generatePath());

        if (! $directory->isWritable($directoryPath)) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.index.directory.not-writable', [
                    'type'       => 'file',
                    'actionType' => 'move',
                    'path'       => $directoryPath,
                ]),
            ], 500);
        }

        $oldAssetFullPath = $asset->path;

        $asset->directories()->sync($request->input('new_parent_id'));
        $newDirectory = $asset->directories()->first();
        $directoryPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $newDirectory->generatePath());
        $uniqueFileName = $this->generateUniqueFileName($directoryPath, $asset->file_name);
        $newPath = sprintf('%s/%s', $newDirectory->generatePath(), $uniqueFileName);
        $newAssetFullPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $newPath);
        $asset->update([
            'path'      => $newAssetFullPath,
            'file_name' => $uniqueFileName,
        ]);

        $disk = Directory::getAssetDisk();
        Storage::disk($disk)->makeDirectory($directoryPath);

        if (
            $oldAssetFullPath
            && $oldAssetFullPath !== $newAssetFullPath
            && Storage::disk($disk)->exists($oldAssetFullPath)
        ) {
            Storage::disk($disk)->move($oldAssetFullPath, $newAssetFullPath);
        }

        return new JsonResponse([
            'data'    => $asset,
            'message' => trans('dam::app.admin.dam.index.directory.asset-moved-success'),
        ]);
    }

    private function clearAssetCache(string $path, string $disk, ?int $assetId = null): void
    {
        Storage::disk($disk)->delete('thumbnails/'.$path);

        foreach (Storage::disk($disk)->directories('preview') as $sizeDir) {
            Storage::disk($disk)->delete($sizeDir.'/'.$path);
        }

        if ($assetId !== null) {
            foreach (['jpg', 'png', 'gif', 'webp'] as $ext) {
                Storage::disk($disk)->delete('covers/'.$assetId.'.'.$ext);
            }
        }
    }

    protected function humanReadableSize(int $kilobytes): string
    {
        if ($kilobytes >= 1024 * 1024) {
            return round($kilobytes / 1024 / 1024, 2).' GB';
        }

        if ($kilobytes >= 1024) {
            return round($kilobytes / 1024, 2).' MB';
        }

        return $kilobytes.' KB';
    }

    protected function mappedWithDirectory($assetIds, $directoryId): ?Directory
    {
        $directory = $this->directoryRepository->find($directoryId);

        if (! $directory) {
            return null;
        }

        $directory->assets()->attach($assetIds);

        return $directory;
    }

    private function autoGrantToCreator(int $directoryId): void
    {
        $admin = auth()->guard('admin')->user();

        if (! $admin) {
            return;
        }

        $role = $admin->role;

        if (! $role || $role->permission_type !== 'custom') {
            return;
        }

        if (DB::table('dam_role_settings')->where('role_id', $role->id)->where('all_directories', true)->exists()) {
            return;
        }

        $this->permissionRepository->addDirectoryToRole($role->id, $directoryId);
        $this->permissionService->flush();
    }
}
