<?php

namespace Webkul\DAM\Http\Controllers\Asset;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\DAM\DataGrids\Asset\AssetDataGrid;
use Webkul\DAM\Filesystem\FileStorer;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Repositories\AssetTagRepository;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Traits\Directory as DirectoryTrait;

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
        protected DirectoryRepository $directoryRepository
    ) {}

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
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $asset = $this->assetRepository->find($id);

        if (! $asset) {
            abort(404);
        }

        $asset->previewPath = route('admin.dam.file.preview', ['path' => urlencode($asset->path), 'size' => '1356']);

        $disk = Directory::getAssetDisk();
        if ($asset->file_type === 'image') {
            $metaData = $this->getMetadata($asset->path, $disk);

            if ($metaData['success']) {
                // fix: Remove problematic metadata entries to prevent errors

                if (isset($metaData['data']['UndefinedTag:0xEA1C'])) {
                    unset($metaData['data']['UndefinedTag:0xEA1C']);
                }

                $asset->embeddedMetaInfo = $metaData['data'] ?? [];
            }
        }

        $asset->comments = $asset->comments()->orderBy('created_at', 'desc')->get();

        $tags = $this->assetTagRepository->all();

        return view('dam::asset.edit', compact('asset', 'id', 'tags'));
    }

    /**
     * Get metadata for a given file
     */
    public function getMetadata(string $path, string $disk)
    {
        try {
            $storage = Storage::disk($disk);

            if (! $storage->exists($path)) {
                throw new \Exception(trans('dam::app.admin.dam.asset.edit.image-source-not-readable'));
            }

            $fileContent = $storage->get($path);
            $image = Image::make($fileContent);

            $data = [
                'Width'     => $image->width(),
                'Height'    => $image->height(),
            ];

            return [
                'success' => true,
                'data'    => $data,
            ];
        } catch (\Exception $e) {
            report($e);

            return [
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.edit.failed-to-read', ['exception' => $e->getMessage()]),
            ];
        }
    }

    /**
     * to upload the asset
     *
     * @return void
     */
    public function upload(Request $request)
    {
        $request->validate([
            'files'        => 'required|array',
            'files.*'      => 'file',
            'directory_id' => 'required|exists:dam_directories,id',
        ]);

        $files = $request->file('files');
        $directoryId = $request->get('directory_id');

        $directory = $this->directoryRepository->find($directoryId);
        $directoryPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $directory->generatePath());

        $uploadFiles = [];
        $assetIds = [];

        try {
            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {

                    $extension = strtolower($file->getClientOriginalExtension());
                    $mimeType = $file->getMimeType();

                    if (AssetHelper::isForbiddenFile($extension, $mimeType)) {
                        throw new \Exception(trans('dam::app.admin.dam.index.directory.not-allowed'));
                    }

                    $originalName = $file->getClientOriginalName();
                    $uniqueFileName = $this->generateUniqueFileName($directoryPath, $originalName);

                    if (! $directory->isWritable($directoryPath)) {
                        throw new \Exception(trans('dam::app.admin.dam.index.directory.not-writable', [
                            'type'       => 'file',
                            'actionType' => 'create',
                            'path'       => $directoryPath,
                        ]));
                    }

                    $disk = Directory::getAssetDisk();

                    $filePath = $this->fileStorer->store(
                        path: $directoryPath,
                        file: $file,
                        fileName: $uniqueFileName,
                        options: [FileStorer::HASHED_FOLDER_NAME_KEY => false, 'disk' => $disk]
                    );

                    $asset = Asset::create([
                        'file_name' => $uniqueFileName,
                        'file_type' => AssetHelper::getFileType($file),
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'extension' => $file->getClientOriginalExtension(),
                        'path'      => $filePath,
                    ]);
                    $assetIds[] = $asset->id;
                    array_push($uploadFiles, $asset);
                }
            }

            if ($request->has('directory_id')) {
                $this->mappedWithDirectory($assetIds, $request->get('directory_id'));
            }

            return response()->json([
                'success' => true,
                'files'   => $uploadFiles,
                'message' => count($files) > 1 ? trans('dam::app.admin.dam.asset.datagrid.files-upload-success') : trans('dam::app.admin.dam.asset.datagrid.file-upload-success'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * to Re upload the asset
     *
     * @return void
     */
    public function reUpload(Request $request)
    {
        $request->validate([
            'file'     => 'required',
            'file.*'   => 'file',
            'asset_id' => 'required|exists:dam_assets,id',
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
            Storage::disk($disk)->delete($asset->path);

            $originalName = $file->getClientOriginalName();
            $uniqueFileName = $this->generateUniqueFileName($directoryPath, $originalName);

            if (! $directory->isWritable($directoryPath)) {
                throw new \Exception(trans('dam::app.admin.dam.index.directory.not-writable', [
                    'type'       => 'file',
                    'actionType' => 'create',
                    'path'       => $directoryPath,
                ]));
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
            ]);
        }

        return response()->json([
            'success' => true,
            'file'    => $asset,
            'message' => trans('dam::app.admin.dam.asset.edit.file-re-upload-success'),
        ], 201);
    }

    /**
     * To Display the asset.
     *
     * @param [type] $id
     * @return void
     */
    public function show($id)
    {
        $asset = Asset::find($id);

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found-to-show'), // asset not found for show
            ], 404);
        }

        return response()->json([
            'success' => true,
            'asset'   => $asset,
        ]);
    }

    /**
     * To update the asset
     *
     * @param [type] $id
     * @return void
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
     * @return void
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

        return Storage::disk($disk)->download($asset->path);
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

        // Svg Image Download
        if ($asset->extension === 'svg' && $format) {
            $svgContent = Storage::disk($disk)->get($asset->path);

            $imagick = new \Imagick;
            $imagick->readImageBlob($svgContent);
            $imagick->setImageFormat($format);

            $fileName = pathinfo($asset->file_name, PATHINFO_FILENAME).'.'.$format;
            $tempFilePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$fileName;
            $imagick->writeImage($tempFilePath);

            return response()->download($tempFilePath, $fileName)->deleteFileAfterSend(true);
        }

        // Image Download
        if ($asset->file_type === 'image' && ($format || $height || $width)) {
            try {
                $disk = Directory::getAssetDisk();
                $fileContent = Storage::disk($disk)->get($asset->path);
                $image = Image::make($fileContent);

                if ($width || $height) {
                    $image->resize($width, $height, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                }

                if ($format) {
                    $image->encode($format);
                    $fileName = pathinfo($asset->file_name, PATHINFO_FILENAME).'.'.$format;
                } else {
                    $fileName = $asset->file_name;
                }

                $tempFilePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$fileName;
                $image->save($tempFilePath);

                return response()->download($tempFilePath, $fileName)->deleteFileAfterSend(true);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Image processing failed: '.$e->getMessage(),
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
        $oldDirectory = $asset->directories()->first();
        $oldPath = sprintf('%s/%s', $oldDirectory->generatePath(), $asset->file_name);

        $directory = $this->directoryRepository->find($request->input('new_parent_id'));

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

        $asset->directories()->sync($request->input('new_parent_id'));
        $newDirectory = $asset->directories()->first();
        $directoryPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $newDirectory->generatePath());
        $uniqueFileName = $this->generateUniqueFileName($directoryPath, $asset->file_name);
        $newPath = sprintf('%s/%s', $newDirectory->generatePath(), $uniqueFileName);
        $asset->update([
            'path'      => sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $newPath),
            'file_name' => $uniqueFileName,
        ]);

        $this->directoryRepository->createDirectoryWithStorage($newPath, $oldPath);

        return new JsonResponse([
            'data'    => $asset,
            'message' => trans('dam::app.admin.dam.index.directory.asset-moved-success'),
        ]);
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
