<?php

namespace Webkul\DAM\Http\Controllers\Asset;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\DAM\DataGrids\Asset\AssetDataGrid;
use Webkul\DAM\Filesystem\FileStorer;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetComments;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Repositories\AssetTagRepository;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\DAM\Services\MetadataExtractionService;
use Webkul\DAM\Traits\Directory as DirectoryTrait;
use ZipArchive;

class AssetController extends Controller
{
    use DirectoryTrait;

    /**
     *  Create instance
     */
    public function __construct(
        protected AssetRepository $assetRepository,
        protected AssetTagRepository $assetTagRepository,
        protected FileStorer $fileStorer,
        protected DirectoryRepository $directoryRepository,
        protected MetadataExtractionService $metadataExtractionService,
        protected DirectoryPermissionService $permissionService,
    ) {}

    /**
     * Resolve the directory id an asset belongs to. Assets are linked through
     * the dam_asset_directory pivot; an asset typically lives in exactly one
     * directory.
     */
    protected function assetDirectoryId(?Asset $asset): ?int
    {
        if (! $asset) {
            return null;
        }

        $dirId = $asset->directories()->value('dam_directories.id');

        return $dirId ? (int) $dirId : null;
    }

    /**
     * Returns true when the current admin can act on this asset based on its
     * containing directory. Strict (directly-granted only) — ancestors that are
     * tree-visible via expansion don't count.
     */
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

    /**
     * Main route
     *
     * @return void
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(AssetDataGrid::class)->toJson();
        }

        return view('dam::asset.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return View
     */
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

        $asset = $this->getNextAndPreviousAssets($asset, $id);

        $assetModel = $this->assetRepository->model();
        $assetPosition = $assetModel::where('id', '<=', $id)->count();
        $assetTotal = $assetModel::count();

        $bytes = (int) ($asset->file_size ?? 0);
        $fileSize = $bytes >= 1048576
            ? number_format($bytes / 1048576, 2).' MB'
            : ($bytes >= 1024 ? number_format($bytes / 1024, 1).' KB' : ($bytes > 0 ? $bytes.' B' : null));

        $directory = $asset->directories()->first();
        $directoryAncestors = $directory
            ? $directory->ancestorsAndSelfAndDefaultOrder($directory->id)
            : collect();

        return view('dam::asset.edit', compact('asset', 'id', 'tags', 'assetPosition', 'assetTotal', 'fileSize', 'directoryAncestors'));
    }

    /**
     * Get next and previous assets based on the current asset ID.
     *
     * @param  Asset  $asset
     * @param  int  $id
     * @return Asset
     */
    protected function getNextAndPreviousAssets($asset, $id)
    {
        $assetModel = $this->assetRepository->model();

        $nextAsset = $assetModel::where('id', '>', $id)->orderBy('id', 'asc')->first();
        $asset->nextAssetId = $nextAsset ? $nextAsset->id : null;

        $previousAsset = $assetModel::where('id', '<', $id)->orderBy('id', 'desc')->first();
        $asset->previousAssetId = $previousAsset ? $previousAsset->id : null;

        return $asset;
    }

    /**
     * Get metadata for a given by asset id
     */
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

    /**
     * Flatten scalar EXIF entries into the top-level metadata array.
     *
     * The stored `meta_data` (and the shape returned by MetadataExtractionService) contains an
     * `exif` key whose scalar children should be merged alongside the other top-level fields,
     * while nested array children under `exif` are preserved. The returned array no longer
     * contains the `exif` key when flattening succeeds.
     */
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

    /**
     * to upload the asset
     *
     * @return void|JsonResponse
     */
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

        // Writability is request-scoped (same directory for every file in the
        // batch), so check once up front rather than re-checking inside the loop.
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

        // Pre-fetch any existing assets that match the incoming filenames in
        // this directory. Avoids N round-trips for an N-file batch.
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

                if (AssetHelper::isForbiddenFile($extension, $mimeType)) {
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

                $localFilePath = $file->getRealPath();
                $metaData = $this->metadataExtractionService->extractMetadata($localFilePath, disk: 'local', originalFileName: $originalName);

                $payload = [
                    'file_name' => $fileName,
                    'file_type' => AssetHelper::getFileType($file),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'extension' => $file->getClientOriginalExtension(),
                    'path'      => $filePath,
                    'meta_data' => $metaData,
                ];

                if ($isOverwrite) {
                    $existingAsset->update($payload);
                    $asset = $existingAsset;
                } else {
                    $asset = Asset::create($payload);
                    $assetIds[] = $asset->id;
                }

                $this->attachAudioCoverArt($asset, $localFilePath, $mimeType, $metaData, $disk);

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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * For audio assets, extract embedded cover art and attach its storage path
     * to the asset's meta_data. No-op for non-audio mime types or when no
     * embedded artwork is found.
     */
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

    /**
     * to Re upload the asset
     *
     * @return void|JsonResponse
     */
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
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found'), // asset not found
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
            if (AssetHelper::isForbiddenFile($extension, $mimeType)) {
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
        }

        return response()->json([
            'success' => true,
            'file'    => $asset,
            'message' => trans('dam::app.admin.dam.asset.edit.file-re-upload-success'),
        ], 201);
    }

    /**
     * To Display the asset — JSON payload for SPA navigation.
     *
     * @param [type] $id
     * @return void|JsonResponse
     */
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

        $mediaUrl = route('admin.dam.file.preview', ['path' => urlencode($asset->path)]);

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
        $nextAsset = $assetModel::where('id', '>', $id)->orderBy('id', 'asc')->first();
        $prevAsset = $assetModel::where('id', '<', $id)->orderBy('id', 'desc')->first();
        $assetPosition = $assetModel::where('id', '<=', $id)->count();
        $assetTotal = $assetModel::count();

        $tags = $asset->tags()->get(['dam_tags.id', 'dam_tags.name']);

        return response()->json([
            'success'               => true,
            'asset'                 => $asset,
            'assetPath'             => $asset->getPathWithOutFileSystemRoot() ?? '',
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

    /**
     * To update the asset
     *
     * @param [type] $id
     * @return void|JsonResponse
     */
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

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.asset.datagrid.update-success'),
            'asset'   => $asset,
        ]);
    }

    /**
     * Delete asset
     *
     * @param [type] $id
     * @return void|JsonResponse
     */
    public function destroy($id)
    {
        $asset = Asset::find($id);

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found-to-destroy'), // Asset not found to destroy
            ], 404);
        }

        if (! $this->canActOnAsset($asset)) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

        if ($asset->resources()->get()->count()) {
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

    /**
     * Mass delete assets
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $assetIds = $massDestroyRequest->input('indices');
        $skippedAssetNames = [];

        try {
            foreach ($assetIds as $assetId) {
                $asset = $this->assetRepository->find($assetId);

                if (isset($asset)) {
                    if ($asset->resources()->get()->count()) {
                        $skippedAssetNames[] = $asset->file_name;

                        continue;
                    }
                    $disk = Directory::getAssetDisk();

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

                    $this->assetRepository->delete($assetId);

                    Event::dispatch('dam.asset.delete.after', $assetId);
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
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mass update assets
     */
    public function massUpdate(MassUpdateRequest $massUpdateRequest): JsonResponse
    {
        $data = $massUpdateRequest->all();

        $assetIds = $data['indices'];

        foreach ($assetIds as $assetId) {
            Event::dispatch('dam.asset.update.before', $assetId);

            // $asset = $this->assetRepository->updateStatus();

            Event::dispatch('dam.asset.update.after', $assetId);
        }

        return new JsonResponse([
            'message' => trans('dam::app.admin.dam.asset.datagrid.mass-update-success'),
        ]);
    }

    /**
     * Download
     */
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
                // fall through to streaming download
            }
        }

        return Storage::disk($disk)->download($asset->path);
    }

    /**
     * Download asset wrapped in a ZIP archive.
     */
    public function downloadCompressed(int $id)
    {
        $asset = Asset::find($id);
        $disk = Directory::getAssetDisk();

        if (! $asset || ! Storage::disk($disk)->exists($asset->path)) {
            abort(404);
        }

        abort_unless($this->canActOnAsset($asset), 403, trans('dam::app.admin.permissions.unauthorized'));

        $baseName = pathinfo($asset->file_name, PATHINFO_FILENAME);
        $zipFileName = $baseName.'_'.uniqid().'.zip';
        $zipPath = public_path($zipFileName);

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            abort(500);
        }

        $zip->addFromString($asset->file_name, Storage::disk($disk)->get($asset->path));
        $zip->close();

        return response()->download($zipPath, $baseName.'.zip')->deleteFileAfterSend(true);
    }

    /**
     * Custom download functionality for images, allowing adjustments in size and format.
     *
     * Handles image assets by providing options to resize the image to specified dimensions
     * and change the image format while initiating a download. If the asset type is image,
     * users can specify the desired width, height, and format to customize their download.
     * Non-image assets will be downloaded in their original form without any modifications.
     */
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

        // Svg Image Download
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

        // Image Download
        if ($asset->file_type === 'image' && ($format || $height || $width)) {
            try {
                $disk = Directory::getAssetDisk();
                $fileContent = Storage::disk($disk)->get($asset->path);
                $image = (new ImageManager(new Driver))->read($fileContent);

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

    /**
     * Rename asset file name
     */
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
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Moved asset directory location
     */
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

    /**
     * Format a kilobyte value into a human readable string (e.g. "50 MB").
     */
    private function clearAssetCache(string $path, string $disk, ?int $assetId = null): void
    {
        Storage::disk($disk)->delete('thumbnails/'.$path);

        foreach (Storage::disk($disk)->allFiles('preview') as $previewFile) {
            if (str_ends_with($previewFile, '/'.$path)) {
                Storage::disk($disk)->delete($previewFile);
            }
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

    /**
     * Mapped asset with directory
     */
    protected function mappedWithDirectory($assetIds, $directoryId): ?Directory
    {
        $directory = $this->directoryRepository->find($directoryId);

        if (! $directory) {
            return null;
        }

        $directory->assets()->attach($assetIds);

        return $directory;
    }
}
