<?php

namespace Webkul\DAM\Http\Controllers\API\Asset;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DAM\ApiDataSource\AssetDataSource;
use Webkul\DAM\Filesystem\FileStorer;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Jobs\GeneratePdfThumbnail;
use Webkul\DAM\Jobs\GenerateVideoThumbnail;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\Tag;
use Webkul\DAM\Repositories\AssetPropertyRepository;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Repositories\AssetTagRepository;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\DAM\Services\MetadataExtractionService;
use Webkul\DAM\Traits\AssetAccessControl;
use Webkul\DAM\Traits\Directory as DirectoryTrait;

class AssetController extends Controller
{
    use AssetAccessControl;
    use DirectoryTrait;

    public function __construct(
        protected AssetRepository $assetRepository,
        protected AssetTagRepository $assetTagRepository,
        protected AssetPropertyRepository $assetPropertyRepository,
        protected FileStorer $fileStorer,
        protected DirectoryRepository $directoryRepository,
        protected MetadataExtractionService $metadataExtractionService
    ) {}

    public function index(): JsonResponse
    {
        return app(AssetDataSource::class)->toJson();
    }

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
                if (! $this->isSafeRemoteUrl($url)) {
                    $errors[] = "Blocked URL: $url";

                    continue;
                }

                $path = parse_url($url, PHP_URL_PATH);
                $fileName = basename($path);

                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                if (! $extension) {
                    $headResponse = Http::withOptions(['allow_redirects' => false])->head($url);
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

                $response = Http::sink($tempPath)->withOptions(['allow_redirects' => false])->get($url);

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

    private function isSafeRemoteUrl(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = trim($parts['host'], '[]');

        $ips = [];

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
        } else {
            foreach (@dns_get_record($host, DNS_A + DNS_AAAA) ?: [] as $record) {
                if (! empty($record['ip'])) {
                    $ips[] = $record['ip'];
                }

                if (! empty($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }

            if (empty($ips)) {
                $ips = gethostbynamel($host) ?: [];
            }
        }

        if (empty($ips)) {
            return false;
        }

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }

    public function upload(Request $request): JsonResponse
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $request->validate([
            'directory_id' => 'required|exists:dam_directories,id',
        ]);

        $maxKb = AssetHelper::getMaxUploadSizeKb();
        $sizeMessage = trans('dam::app.admin.dam.asset.datagrid.file-too-large', [
            'size' => $this->humanReadableSize($maxKb),
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
                'files.*' => 'file|max:'.$maxKb,
            ], [
                'files.*.max'      => $sizeMessage,
                'files.*.uploaded' => $sizeMessage,
                'files.*.file'     => $sizeMessage,
            ]);
            $files = $request->file('files');
        }

        $directoryId = $request->get('directory_id');

        $service = app(DirectoryPermissionService::class);
        if (! $service->canAccess((int) $directoryId)) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.permissions.unauthorized'),
            ], 403);
        }

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

            if (AssetHelper::isForbiddenFile($extension, $mimeType, $file->getClientOriginalName(), $file->getRealPath())) {
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

                $localFilePath = $file->getRealPath();
                $metaData = $this->metadataExtractionService->extractMetadata($localFilePath, disk: 'local', originalFileName: $originalName);

                $asset = Asset::create([
                    'file_name' => $uniqueFileName,
                    'file_type' => AssetHelper::getFileType($file),
                    'file_size' => $file->getSize(),
                    'mime_type' => $mimeType,
                    'extension' => $extension,
                    'path'      => $filePath,
                    'meta_data' => json_encode($metaData),
                ]);

                $this->attachAudioCoverArt($asset, $localFilePath, $mimeType, $metaData, $disk);

                if ($asset->file_type === 'video') {
                    GenerateVideoThumbnail::dispatch($asset->id)->afterCommit();
                } elseif (strtolower((string) $asset->extension) === 'pdf') {
                    GeneratePdfThumbnail::dispatch($asset->id)->afterCommit();
                }

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

        $this->damAuthorizeAsset($assetId);

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

            if (AssetHelper::isForbiddenFile($extension, $mimeType, $file->getClientOriginalName(), $file->getRealPath())) {
                return response()->json([
                    'success' => false,
                    'message' => trans('dam::app.admin.dam.index.directory.not-allowed'),
                ], 400);
            }

            $disk = Directory::getAssetDisk();
            Storage::disk($disk)->delete($asset->path);
            $originalName = $file->getClientOriginalName();
            $uniqueFileName = $this->generateUniqueFileName($directoryPath, $originalName);

            $localFilePath = $file->getRealPath();
            $metaData = $this->metadataExtractionService->extractMetadata($localFilePath, disk: 'local', originalFileName: $originalName);

            if (str_starts_with($mimeType ?? '', 'audio/') && $localFilePath && file_exists($localFilePath)) {
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
            'message' => trans('dam::app.admin.dam.asset.edit.file-re-upload-success'),
            'file'    => $asset,
        ], 201);
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

        $this->damAuthorizeAsset($id);

        $asset->previewPath = AssetHelper::getPreviewUrl(
            $asset->path,
            $asset->file_size
        );

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

    public function metadata(int $id): JsonResponse
    {
        $asset = $this->assetRepository->find($id);

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found'),
            ], 404);
        }

        $this->damAuthorizeAsset($id);

        $metaData = $asset->meta_data ?? [];

        if (is_string($metaData)) {
            $decoded = json_decode($metaData, true);
            $metaData = is_array($decoded) ? $decoded : [];
        }

        if (empty($metaData) && $asset->file_type === 'image') {
            $result = $this->getMetadata($asset->path, Directory::getAssetDisk());

            if (! empty($result['success'])) {
                if (isset($result['data']['UndefinedTag:0xEA1C'])) {
                    unset($result['data']['UndefinedTag:0xEA1C']);
                }

                $metaData = $result['data'] ?? [];
            }
        }

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.asset.datagrid.show-success'),
            'data'    => [
                'asset_id'  => $asset->id,
                'file_name' => $asset->file_name,
                'file_type' => $asset->file_type,
                'mime_type' => $asset->mime_type,
                'meta_data' => $metaData,
            ],
        ], 200);
    }

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

        $this->damAuthorizeAsset($id);

        $asset->previewPath = AssetHelper::getPreviewUrl(
            $asset->path,
            1356
        );

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

    public function update(Request $request, $id): JsonResponse
    {
        $asset = Asset::find($id);

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found-to-update'),
            ], 404);
        }

        $this->damAuthorizeAsset((int) $id);

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

    public function destroy($id): JsonResponse
    {
        $asset = Asset::find($id);

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found-to-destroy'),
            ], 404);
        }

        $this->damAuthorizeAsset((int) $id);

        if ($asset->resources()->exists()) {
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
        $this->damAuthorizeAsset($id);

        $asset = Asset::find($id);
        $disk = Directory::getAssetDisk();

        if (! $asset || ! Storage::disk($disk)->exists($asset->path)) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.datagrid.not-found-or-no-file'),
            ], 404);
        }

        $fileName = $asset->file_name ?? basename($asset->path);

        if (config('filesystems.default') === 's3') {
            $downloadUrl = Storage::disk($disk)->temporaryUrl(
                $asset->path,
                now()->addMinutes(10),
                [
                    'ResponseContentDisposition' => 'attachment; filename="'.$fileName.'"',
                ]
            );
        } else {
            $downloadUrl = URL::temporarySignedRoute(
                'admin.api.dam.assets.private.download',
                now()->addMinutes(10),
                ['id' => $asset->id]
            );
        }

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.asset.datagrid.download-link-ready'),
            'data'    => [
                'download_url' => $downloadUrl,
            ],
        ], 200);
    }
}
