<?php

namespace Webkul\DAM\Http\Controllers\API\Asset;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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
     * Helper function to upload Asset by using Public Url
     */
    public function downloadAndConvertFiles(Request $request)
    {
        $imageUrls = $request->input('files');
        if (empty($imageUrls) || ! is_array($imageUrls)) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.invalid-file-format-or-not-provided'),
            ], 422);
        }

        $newImageUrlString = $imageUrls[0];
        $newImageUrl = explode(',', $newImageUrlString);
        $newImageUrl = array_map(function ($url) {
            return trim($url, ' "');
        }, $newImageUrl);

        $files = [];
        $errors = [];

        foreach ($newImageUrl as $url) {
            try {
                $path = parse_url($url, PHP_URL_PATH);
                $fileName = basename($path);

                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                if (! $extension) {
                    $headResponse = Http::head($url);
                    $contentType = $headResponse->header('Content-Type');
                    $extension = match ($contentType) {
                        'image/jpeg'      => 'jpg',
                        'image/png'       => 'png',
                        'application/pdf' => 'pdf',
                        'video/mp4'       => 'mp4',
                        default           => 'bin',
                    };
                    $fileName .= '.'.$extension;
                }

                $tempPath = sys_get_temp_dir().'/'.uniqid().'_'.$fileName;

                $response = Http::sink($tempPath)->get($url);

                if ($response->failed() || ! file_exists($tempPath) || filesize($tempPath) === 0) {
                    $errors[] = "Failed to download: $url";

                    continue;
                }

                $mimeType = mime_content_type($tempPath);

                $uploadedFile = new UploadedFile(
                    $tempPath,
                    $fileName,
                    $mimeType,
                    filesize($tempPath),
                    UPLOAD_ERR_OK,
                    true
                );
                $files[] = $uploadedFile;
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (! empty($errors)) {
            return response()->json([
                'success'         => false,
                'message'         => trans('dam::app.admin.dam.asset.datagrid.file-process-failed'),
                'errors'          => $errors,
                'files_processed' => count($files),
            ], 422);
        }

        return $files;
    }

    /**
     * to upload the asset
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'directory_id' => 'required|exists:dam_directories,id',
        ]);

        $files = [];

        if ($request->has('files') && ! $request->hasFile('files')) {
            $files = $this->downloadAndConvertFiles($request);
            if ($files instanceof JsonResponse) {
                return $files;
            }
        } else {
            $request->validate([
                'files'   => 'required|array',
                'files.*' => 'file',
            ]);
            $files = $request->file('files');
        }

        $directoryId = $request->get('directory_id');
        $directory = $this->directoryRepository->find($directoryId);
        $directoryPath = sprintf('%s/%s', Directory::ASSETS_DIRECTORY, $directory->generatePath());
        $disk = Directory::getAssetDisk();

        $uploadFiles = [];
        $assetIds = [];
        $errors = [];

        foreach ($files as $file) {
            if (! ($file instanceof UploadedFile)) {
                $errors[] = trans('dam::app.admin.dam.asset.datagrid.invalid-file');

                continue;
            }

            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();

            if (AssetHelper::isForbiddenFile($extension, $mimeType)) {
                $errors[] = trans('dam::app.admin.dam.asset.datagrid.file-forbidden-type').': '.$file->getClientOriginalName();

                continue;
            }

            if (! $directory->isWritable($directoryPath)) {
                $errors[] = trans('dam::app.admin.dam.index.directory.not-writable', [
                    'actionType' => 'write',
                    'type'       => 'directory',
                    'path'       => $directoryPath,
                ]);
                break;
            }

            try {
                $originalName = $file->getClientOriginalName();
                $uniqueFileName = $this->generateUniqueFileName($directoryPath, $originalName);

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
                    'mime_type' => $mimeType,
                    'extension' => $extension,
                    'path'      => $filePath,
                ]);

                $assetIds[] = $asset->id;
                $uploadFiles[] = $asset;
            } catch (\Exception $e) {
                $errors[] = trans('dam::app.admin.dam.asset.datagrid.file-upload-failed').': '.$file->getClientOriginalName().' : '.$e->getMessage();
            }
        }

        if ($request->has('directory_id')) {
            $this->mappedWithDirectory($assetIds, $request->get('directory_id'));
        }

        $response = [
            'success' => count($errors) === 0,
            'files'   => $uploadFiles,
            'message' => count($uploadFiles) > 1
                ? trans('dam::app.admin.dam.asset.datagrid.files-upload-success')
                : trans('dam::app.admin.dam.asset.datagrid.file-upload-success'),
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
            $response['message'] = trans('dam::app.admin.dam.asset.datagrid.files-upload-failed');
        }

        return response()->json($response, count($errors) === 0 ? 201 : 422);
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
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();

            if (AssetHelper::isForbiddenFile($extension, $mimeType)) {
                return response()->json([
                    'success' => false,
                    'message' => trans('dam::app.admin.dam.index.directory.not-allowed'),
                ], 400);
            }

            $disk = Directory::getAssetDisk();
            Storage::disk($disk)->delete($asset->path);
            $originalName = $file->getClientOriginalName();
            $uniqueFileName = $this->generateUniqueFileName($directoryPath, $originalName);

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
            'message' => trans('dam::app.admin.dam.asset.edit.file-re-upload-success'),
            'file'    => $asset,
        ], 201);
    }

    /**
     * Display the specified asset.
     */
    public function show(int $id): JsonResponse
    {
        $asset = $this->assetRepository->find($id);
        $disk = Directory::getAssetDisk();

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found'),
            ], 404);
        }

        $asset->previewPath = route('admin.dam.file.preview', ['path' => urlencode($asset->path), 'size' => $asset->file_size]);

        if ($asset->file_type === 'image') {
            $metaData = $this->getMetadata($asset->path, $disk);

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
        $disk = Directory::getAssetDisk();
        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found'),
            ], 404);
        }

        $asset->previewPath = route('admin.dam.file.preview', ['path' => urlencode($asset->path), 'size' => '1356']);

        if ($asset->file_type === 'image') {
            $metaData = $this->getMetadata($asset->path, $disk);

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
            'data'    => $asset,
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

    public function signedUrl(int $id)
    {
        $asset = Asset::find($id);
        $disk = Directory::getAssetDisk();

        if (! $asset || ! Storage::disk($disk)->exists($asset->path)) {
            abort(404);
        }

        return Storage::disk($disk)->download(
            $asset->path,
            $asset->file_name ?? basename($asset->path)
        );
    }

    public function download(int $id)
    {
        $asset = Asset::find($id);
        $disk = Directory::getAssetDisk();

        if (! $asset || ! Storage::disk($disk)->exists($asset->path)) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found or file does not exist.',
            ], 404);
        }

        if ($disk === 'private') {
            $downloadUrl = URL::temporarySignedRoute(
                'admin.api.dam.assets.private.download',
                now()->addMinutes(10),
                ['id' => $asset->id]
            );
        } else {
            $downloadUrl = Storage::disk($disk)->temporaryUrl(
                $asset->path,
                now()->addMinutes(10),
                [
                    'ResponseContentDisposition' => 'attachment; filename="'.($asset->file_name ?? basename($asset->path)).'"',
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Asset found. You can download the file from the provided link.',
            'data'    => [
                'download_url' => $downloadUrl,
            ],
        ], 200);
    }
}
