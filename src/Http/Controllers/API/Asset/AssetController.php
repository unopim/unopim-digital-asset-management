<?php

namespace Webkul\DAM\Http\Controllers\API\Asset;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DAM\DataGrids\Asset\AssetDataGrid;
use Webkul\DAM\Filesystem\FileStorer;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\Tag;
use Webkul\DAM\Repositories\AssetPropertyRepository;
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
        protected AssetPropertyRepository $assetPropertyRepository,
        protected FileStorer $fileStorer,
        protected DirectoryRepository $directoryRepository
    ) {}

    /**
     * Main route
     *
     * @return void
     */
    public function index(): JsonResponse
    {
        try {
            return app(AssetDataGrid::class)->toJson();
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * to upload the asset
     */
    public function upload(Request $request): JsonResponse
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
                        options: [FileStorer::HASHED_FOLDER_NAME_KEY => false, 'disk' => Directory::ASSETS_DISK]
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
                    $uploadFiles[] = $asset;
                }
            }

            if ($request->has('directory_id')) {
                $this->mappedWithDirectory($assetIds, $request->get('directory_id'));
            }

            return response()->json([
                'success' => true,
                'files'   => $uploadFiles,
                'message' => count($files) > 1 ? trans('dam::app.admin.dam.asset.datagrid.files_upload_success') : trans('dam::app.admin.dam.asset.datagrid.file_upload_success'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * to reupload the asset
     *
     * @return JsonResponse
     */
    public function reUpload(Request $request)
    {
        $request->validate([
            'file'     => 'required|file',
            'asset_id' => 'required|exists:dam_assets,id',
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

        $directoryId = $asset->directories()->first()->id ?? null;
        $directory = $this->directoryRepository->find($directoryId);
        $directoryPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $directory->generatePath());

        if (! $directory->isWritable($directoryPath)) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.index.directory.not-writable', [
                    'type'       => 'file',
                    'actionType' => 'create',
                    'path'       => $directoryPath,
                ]),
            ], 403);
        }

        if ($file instanceof UploadedFile) {
            Storage::disk(Directory::ASSETS_DISK)->delete($asset->path);
            $originalName = $file->getClientOriginalName();
            $uniqueFileName = $this->generateUniqueFileName($directoryPath, $originalName);

            $filePath = $this->fileStorer->store(
                path: $directoryPath,
                file: $file,
                fileName: $uniqueFileName,
                options: [FileStorer::HASHED_FOLDER_NAME_KEY => false, 'disk' => Directory::ASSETS_DISK]
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
            'message' => trans('dam::app.admin.dam.asset.edit.file_re_upload_success'),
            'file'    => $asset,
        ], 201);
    }

    /**
     * Display the specified asset.
     */
    public function show(int $id): JsonResponse
    {
        $asset = $this->assetRepository->find($id);

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found'),
            ], 404);
        }

        $asset->previewPath = route('admin.dam.file.preview', ['path' => urlencode($asset->path), 'size' => $asset->file_size]);

        if ($asset->file_type === 'image') {
            $metaData = $this->getMetadata($asset->path);

            if ($metaData['success']) {
                if (isset($metaData['data']['UndefinedTag:0xEA1C'])) {
                    unset($metaData['data']['UndefinedTag:0xEA1C']);
                }

                $asset->embeddedMetaInfo = $metaData['data'] ?? [];
            }
        }

        $asset->resources = $asset->resources()->get();

        $asset->comments = $asset->comments()->orderBy('created_at', 'desc')->get();

        $tags = $this->assetTagRepository->getTagsByAssetId($id);

        $properties = $this->assetPropertyRepository->where('dam_asset_id', $id)->get();

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.asset.datagrid.show-success'),
            'data'    => [
                'asset'    => $asset,
                'tags'     => $tags,
                'property' => $properties,
            ],
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id): JsonResponse
    {
        $asset = $this->assetRepository->find($id);
        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found'),
            ], 404);
        }

        $asset->previewPath = route('admin.dam.file.preview', ['path' => urlencode($asset->path), 'size' => '1356']);

        if ($asset->file_type === 'image') {
            $metaData = $this->getMetadata($asset->path);

            if ($metaData['success']) {

                if (isset($metaData['data']['UndefinedTag:0xEA1C'])) {
                    unset($metaData['data']['UndefinedTag:0xEA1C']);
                }

                $asset->embeddedMetaInfo = $metaData['data'] ?? [];
            }
        }

        $asset->comments = $asset->comments()->orderBy('created_at', 'desc')->get();

        $tags = $this->assetTagRepository->all();

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.asset.datagrid.edit-success'),
            'data'    => [
                'asset'    => $asset,
                'comments' => $asset->comments,
                'tags'     => $tags,
            ],
        ], 200);
    }

    /**
     * To update the asset
     *
     * @param [type] $id
     * @return void
     */
    public function update(Request $request, $id): JsonResponse
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
            'tags'      => 'array',
        ]);

        $asset->update($request->only(['file_name', 'file_type', 'file_size', 'mime_type', 'extension', 'path']));

        if ($request->has('tags')) {
            $invalidTags = array_diff($request->input('tags'), Tag::pluck('id')->toArray());

            if (! empty($invalidTags)) {
                return response()->json([
                    'success' => false,
                    'message' => trans('dam::app.admin.dam.asset.tags.not-found'),
                ], 400);
            }

            $asset->tags()->sync($request->input('tags'));
        }

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.asset.datagrid.update-success'),
        ]);
    }

    /**
     * Delete asset
     *
     * @param [type] $id
     * @return void
     */
    public function destroy($id): JsonResponse
    {
        $asset = Asset::find($id);

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found-to-destroy'),
            ], 404);
        }

        if ($asset->resources()->get()->count()) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.delete-failed-due-to-attached-resources', ['assetNames' => $asset->file_name]),
            ], 404);
        }

        $fileDeleted = Storage::disk(Directory::ASSETS_DISK)->delete($asset->path);

        if (! $fileDeleted) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.index.directory.not-writable', [
                    'type'       => 'file',
                    'actionType' => 'delete',
                    'path'       => $asset->path,
                ])], 500);
        }

        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.asset.delete-success'),
        ]);
    }

    public function download(int $id): JsonResponse
    {
        $asset = Asset::find($id);

        if (! $asset || ! Storage::disk(Directory::ASSETS_DISK)->exists($asset->path)) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found or file does not exist.',
            ], 404);
        }

        $downloadUrl = Storage::disk(Directory::ASSETS_DISK)->url($asset->path);

        return response()->json([
            'success' => true,
            'message' => 'Asset found. You can download the file from the provided link.',
            'data'    => [
                'download_url' => $downloadUrl,
            ],
        ], 200);
    }
}
