<?php

namespace Webkul\DAM\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\AvifEncoder;
use Intervention\Image\Encoders\BmpEncoder;
use Intervention\Image\Encoders\GifEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\TiffEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Jobs\GeneratePdfThumbnail;
use Webkul\DAM\Jobs\GenerateVideoThumbnail;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\DirectoryPermissionService;

class FileController
{
    private const ASSET_CACHE_SECONDS = 86400;

    protected function resolveOriginalAssetPath(string $path): string
    {
        if (Str::startsWith($path, 'thumbnails/')) {
            return Str::after($path, 'thumbnails/');
        }

        if (Str::startsWith($path, 'preview/')) {
            $rest = Str::after($path, 'preview/');

            $slash = strpos($rest, '/');

            return $slash === false ? $rest : substr($rest, $slash + 1);
        }

        return $path;
    }

    protected function assertPathAllowed(string $path)
    {
        $service = app(DirectoryPermissionService::class);
        if ($service->bypass()) {
            return null;
        }

        $original = $this->resolveOriginalAssetPath($path);
        $asset = Asset::where('path', $original)->first();

        if (! $asset) {
            return null;
        }

        $dirId = (int) ($asset->directories()->value('dam_directories.id') ?? 0);

        if (! $dirId || ! $service->canAccess($dirId)) {
            return abort(403, trans('dam::app.admin.permissions.unauthorized'));
        }

        return null;
    }

    public function createFile(Request $request)
    {
        abort_unless(
            bouncer()->hasPermission('dam.asset.upload'),
            403,
            trans('dam::app.admin.permissions.unauthorized')
        );

        $request->validate(['file' => 'required|file']);

        $file = $request->file('file');

        if (AssetHelper::isForbiddenFile(
            strtolower($file->getClientOriginalExtension()),
            $file->getMimeType(),
            $file->getClientOriginalName(),
            $file->getRealPath()
        )) {
            return response()->json(
                ['error' => trans('dam::app.admin.dam.index.directory.not-allowed')],
                422
            );
        }

        $disk = Directory::getAssetDisk();
        $directory = Str::random(10).'/files';
        $path = Storage::disk($disk)->put($directory, $file);

        return response()->json(['path' => $path]);
    }

    public function deleteFile(Request $request)
    {
        abort_unless(
            bouncer()->hasPermission('dam.asset.destroy'),
            403,
            trans('dam::app.admin.permissions.unauthorized')
        );

        $path = (string) $request->path;

        if (! $path || str_contains($path, '..')) {
            return response()->json(['error' => trans('dam::app.admin.dam.file.not-found')], 400);
        }

        $this->assertPathAllowed($path);

        $disk = Directory::getAssetDisk();
        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);

            return response()->json(['status' => trans('dam::app.admin.dam.file.deleted')]);
        } else {
            return response()->json(['error' => trans('dam::app.admin.dam.file.not-found')], 404);
        }
    }

    public function updateFile(Request $request)
    {
        abort_unless(
            bouncer()->hasPermission('dam.asset.re_upload'),
            403,
            trans('dam::app.admin.permissions.unauthorized')
        );

        $request->validate(['file' => 'required|file']);

        $path = (string) $request->path;

        if (! $path || str_contains($path, '..')) {
            return response()->json(['error' => trans('dam::app.admin.dam.file.not-found')], 400);
        }

        $this->assertPathAllowed($path);

        $file = $request->file('file');

        if (AssetHelper::isForbiddenFile(
            strtolower($file->getClientOriginalExtension()),
            $file->getMimeType(),
            $file->getClientOriginalName(),
            $file->getRealPath()
        )) {
            return response()->json(
                ['error' => trans('dam::app.admin.dam.index.directory.not-allowed')],
                422
            );
        }

        $disk = Directory::getAssetDisk();
        if (Storage::disk($disk)->exists($path)) {

            Storage::disk($disk)->delete($path);

            $directory = Str::random(10).'/files';

            $newPath = Storage::disk($disk)->put($directory, $file);

            return response()->json(['new_path' => $newPath]);
        } else {
            return response()->json(['error' => trans('dam::app.admin.dam.file.not-found')], 404);
        }
    }

    public function fetchFile(string $path)
    {
        if (! Auth::check()) {
            return abort(403, trans('dam::app.admin.permissions.unauthorized'));
        }

        $this->assertPathAllowed($path);

        $disk = Directory::getAssetDisk();
        if (Storage::disk($disk)->exists($path)) {
            $mimeType = Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';

            $response = response(Storage::disk($disk)->get($path), 200)
                ->header('Content-Type', $mimeType)
                ->withHeaders(AssetHelper::assetResponseHeaders());

            if (! AssetHelper::isInlineSafeMime($mimeType)) {
                $response->header(
                    'Content-Disposition',
                    'attachment; filename="'.addslashes(basename($path)).'"'
                );
            }

            return $this->applyAssetCache($response);
        } else {
            return response()->json(['error' => trans('dam::app.admin.dam.file.not-found')], 404);
        }
    }

    public function thumbnail()
    {
        $disk = Directory::getAssetDisk();
        if (! Auth::check()) {
            return abort(403, trans('dam::app.admin.permissions.unauthorized'));
        }

        $path = urldecode(request()->path);
        $this->assertPathAllowed($path);

        $asset = Asset::where('path', $path)->first();
        if ($asset && $asset->file_type === 'audio') {
            if ($this->isBrowserNavigation()) {
                $canRedirect = $disk !== Directory::ASSETS_DISK_AWS
                    || Storage::disk($disk)->exists($path);

                if ($canRedirect) {
                    $assetUrl = $this->resolveAssetOpenUrl($disk, $path);

                    if ($assetUrl !== null) {
                        return redirect()->away($assetUrl);
                    }
                }
            }

            $coverPath = $asset->meta_data['cover_art_path'] ?? null;
            if ($coverPath && Storage::disk($disk)->exists($coverPath)) {
                return $this->getFileResponse($coverPath);
            }
        }

        if ($asset && ($asset->file_type === 'video' || strtolower((string) $asset->extension) === 'pdf')) {
            $cached = $asset->meta_data['thumbnail_path'] ?? ('thumbnails/'.$path.'.jpg');

            if (Storage::disk($disk)->exists($cached)) {
                return $this->getFileResponse($cached);
            }

            try {
                $job = strtolower((string) $asset->extension) === 'pdf'
                    ? new GeneratePdfThumbnail($asset->id)
                    : new GenerateVideoThumbnail($asset->id);

                dispatch_sync($job);

                $cached = $asset->fresh()->meta_data['thumbnail_path'] ?? $cached;

                if (Storage::disk($disk)->exists($cached)) {
                    return $this->getFileResponse($cached);
                }
            } catch (\Throwable $e) {
                Log::warning('DAM thumbnail lazy-generation failed: '.$e->getMessage(), ['asset' => $asset->id]);
            }
        }

        $thumbnailPath = 'thumbnails/'.$path;
        if ($this->isImageFile($thumbnailPath, true)) {
            return $this->getFileResponse($thumbnailPath);
        }

        if ($this->isImageFile($path)) {
            $mimeType = Storage::disk($disk)->mimeType($path);
            try {
                $image = $this->resizeImage(Storage::disk($disk)->get($path), 300);

                $imageData = $this->encodeImageByExtension($image, $path);

                Storage::disk($disk)->put($thumbnailPath, $imageData);

                return $this->applyAssetCache(response($imageData, 200)->header('Content-Type', $mimeType));
            } catch (\Throwable $e) {
                Log::warning('DAM thumbnail generation failed: '.$e->getMessage(), ['path' => $path]);
            }
        } elseif ($this->isSvgFile($path)) {
            if (! Storage::disk($disk)->exists($thumbnailPath)) {
                Storage::disk($disk)->copy($path, $thumbnailPath);
            }

            return $this->applyAssetCache(
                response(Storage::disk($disk)->get($thumbnailPath), 200)
                    ->header('Content-Type', 'image/svg+xml')
                    ->withHeaders(AssetHelper::assetResponseHeaders())
            );
        }

        if ($this->isBrowserNavigation() && Storage::disk($disk)->exists($path)) {
            $assetUrl = $this->resolveAssetOpenUrl($disk, $path);

            if ($assetUrl !== null) {
                return redirect()->away($assetUrl);
            }
        }

        return $this->getDefaultThumbnailImage($path);
    }

    private function isBrowserNavigation(): bool
    {
        $accept = (string) request()->header('Accept', '');

        return str_contains($accept, 'text/html');
    }

    private function resolveAssetOpenUrl(string $disk, string $path): ?string
    {
        if ($disk === Directory::ASSETS_DISK_AWS) {
            try {
                $visibility = Storage::disk($disk)->getVisibility($path);

                $url = $visibility === 'public'
                    ? Storage::disk($disk)->url($path)
                    : Storage::disk($disk)->temporaryUrl($path, now()->addMinutes(10));

                return ! empty($url) ? $url : null;
            } catch (\Throwable $e) {
                return null;
            }
        }

        return route('admin.dam.file.fetch', ['path' => $path]);
    }

    private function isImageFile($path, $includeSvg = false)
    {
        $disk = Directory::getAssetDisk();

        if (Storage::disk($disk)->exists($path)) {
            $mimeType = Storage::disk($disk)->mimeType($path);
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            if (strtolower($extension) === 'jfif') {
                $mimeType = 'image/jpeg';
            }

            return $includeSvg ? Str::startsWith($mimeType, 'image/') : Str::startsWith($mimeType, 'image/') && $mimeType !== 'image/svg+xml';
        }

        return false;
    }

    private function isSvgFile($path)
    {
        $disk = Directory::getAssetDisk();

        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->mimeType($path) === 'image/svg+xml';
        }

        return false;
    }

    private function applyAssetCache($response)
    {
        if ($response instanceof BinaryFileResponse) {
            $response->setAutoEtag();
            $response->setAutoLastModified();
        }

        $response->setPrivate();
        $response->setMaxAge(self::ASSET_CACHE_SECONDS);

        $response->isNotModified(request());

        return $response;
    }

    private function getFileResponse($path)
    {
        $disk = Directory::getAssetDisk();

        if ($disk === Directory::ASSETS_DISK_AWS) {
            $visibility = Storage::disk($disk)->getVisibility($path);

            if ($visibility === 'public') {
                $url = Storage::disk($disk)->url($path);

                return redirect($url);
            }

            $url = Storage::disk($disk)->temporaryUrl($path, now()->addMinutes(5));

            return redirect($url);
        }

        $absolutePath = Storage::disk($disk)->path($path);

        return $this->applyAssetCache(
            response()->file($absolutePath, AssetHelper::assetResponseHeaders())
        );
    }

    private function resizeImage($file, $width)
    {
        $manager = new ImageManager(new Driver);

        return $manager->decode($file)->scale(width: $width);
    }

    public function preview()
    {
        $disk = Directory::getAssetDisk();

        if (! Auth::check()) {
            return abort(403, trans('dam::app.admin.permissions.unauthorized'));
        }

        $path = urldecode(request()->path);
        $this->assertPathAllowed($path);
        $customSize = intval(request()->get('size'));

        $maxSize = 1920;
        $customSize = min($maxSize, $customSize);

        $previewDirectory = 'preview/'.$customSize;
        $previewPath = $previewDirectory.'/'.$path;

        if (Storage::disk($disk)->exists($previewPath)) {
            return $this->getFileResponse($previewPath);
        }

        if (Storage::disk($disk)->exists($path)) {
            $mimeType = Storage::disk($disk)->mimeType($path);
            if ($this->isImageFile($path) && $customSize > 0) {
                try {
                    $image = $this->resizeImage(Storage::disk($disk)->get($path), $customSize);

                    $imageData = $this->encodeImageByExtension($image, $path);

                    Storage::disk($disk)->put($previewPath, $imageData);

                    return $this->getFileResponse($previewPath);
                } catch (\Throwable $e) {
                    Log::info('Failed Generating Image preview: '.$e->getMessage());
                }
            } elseif ($this->isSupportedMediaFile($mimeType)) {
                return $this->getFileResponse($path);
            }
        }

        return $this->getDefaultPreviewImage($path);
    }

    private function isSupportedMediaFile($mimeType)
    {
        return Str::startsWith($mimeType, 'image/') ||
            Str::startsWith($mimeType, 'application/pdf') ||
            Str::startsWith($mimeType, 'video/') ||
            Str::startsWith($mimeType, 'audio/');
    }

    private function getDefaultImage($path, $directoryPrefix)
    {
        $extension = File::extension(basename($path));
        $type = AssetHelper::getFileTypeUsingExtension($extension);
        $placeholderPath = 'dam/'.$directoryPrefix.'/'.$type.'.svg';

        if (Storage::disk('public')->exists($placeholderPath)) {
            $mimeType = Storage::disk('public')->mimeType($placeholderPath);
            $fileContent = Storage::disk('public')->get($placeholderPath);

            return $this->applyAssetCache(
                response($fileContent, 200)->header('Content-Type', $mimeType)
            );
        }

        return response()->json(['error' => trans('dam::app.admin.dam.file.not-found')], 404);
    }

    public function coverArt(int $assetId)
    {
        if (! Auth::check()) {
            return abort(403, trans('dam::app.admin.permissions.unauthorized'));
        }

        $disk = Directory::getAssetDisk();
        $asset = Asset::find($assetId);

        if (! $asset) {
            return abort(404);
        }

        $service = app(DirectoryPermissionService::class);
        if (! $service->bypass()) {
            $dirId = (int) ($asset->directories()->value('dam_directories.id') ?? 0);
            if (! $dirId || ! $service->canAccess($dirId)) {
                return abort(403, trans('dam::app.admin.permissions.unauthorized'));
            }
        }

        $path = $asset->meta_data['cover_art_path'] ?? null;

        if (! $path || ! Storage::disk($disk)->exists($path)) {
            return abort(404);
        }

        return $this->getFileResponse($path);
    }

    public function getDefaultThumbnailImage($path)
    {
        return $this->getDefaultImage($path, 'grid');
    }

    public function getDefaultPreviewImage($path)
    {
        return $this->getDefaultImage($path, 'preview');
    }

    private function encodeImageByExtension($image, $path)
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'png'                 => $image->encode(new PngEncoder),
            'webp'                => $image->encode(new WebpEncoder),
            'gif'                 => $image->encode(new GifEncoder),
            'bmp'                 => $image->encode(new BmpEncoder),
            'tiff', 'tif'         => $image->encode(new TiffEncoder),
            'avif'                => $image->encode(new AvifEncoder),
            'jpg', 'jpeg', 'jfif' => $image->encode(new JpegEncoder),
            default               => $image->encode(new JpegEncoder),
        };
    }
}
