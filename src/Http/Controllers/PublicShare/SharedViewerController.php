<?php

namespace Webkul\DAM\Http\Controllers\PublicShare;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\Share;
use Webkul\DAM\Repositories\ShareRepository;

class SharedViewerController extends Controller
{
    public function __construct(
        protected ShareRepository $shareRepository,
    ) {}

    /**
     * Public landing page for a share token.
     */
    public function show(string $token)
    {
        $share = $this->shareRepository->findByToken($token);

        if (! $share) {
            return $this->renderNotFound();
        }

        if (! $share->isActive()) {
            return $this->renderExpired($share);
        }

        $this->shareRepository->incrementView($share);

        if ($share->share_type === Share::TYPE_ASSET) {
            $asset = $share->asset;

            if (! $asset) {
                return $this->renderNotFound();
            }

            return view('dam::share.public.asset', [
                'share' => $share,
                'asset' => $asset,
            ]);
        }

        $directory = $share->directory;
        if (! $directory) {
            return $this->renderNotFound();
        }

        $assets = $directory->assets()
            ->orderByDesc('dam_assets.updated_at')
            ->get();

        return view('dam::share.public.directory', [
            'share'     => $share,
            'directory' => $directory,
            'assets'    => $assets,
        ]);
    }

    /**
     * Download the asset referenced by an asset-share token.
     */
    public function download(Request $request, string $token)
    {
        $share = $this->shareRepository->findActiveByToken($token);

        if (! $share || $share->share_type !== Share::TYPE_ASSET) {
            return $this->renderExpiredOrNotFound($token);
        }

        $asset = $share->asset;
        if (! $asset) {
            return $this->renderNotFound();
        }

        return $this->streamAsset(
            $asset,
            $request->query('disposition', 'attachment'),
            fn () => $this->shareRepository->incrementDownload($share)
        );
    }

    /**
     * Detail view for a single asset that lives inside a shared directory.
     */
    public function assetView(string $token, int $assetId)
    {
        $share = $this->shareRepository->findActiveByToken($token);

        if (! $share || $share->share_type !== Share::TYPE_DIRECTORY) {
            return $this->renderExpiredOrNotFound($token);
        }

        $asset = $this->resolveDirectoryAsset($share, $assetId);
        if (! $asset) {
            return $this->renderNotFound();
        }

        return view('dam::share.public.asset', [
            'share' => $share,
            'asset' => $asset,
        ]);
    }

    /**
     * Download an asset that lives inside a shared directory.
     */
    public function assetDownload(Request $request, string $token, int $assetId)
    {
        $share = $this->shareRepository->findActiveByToken($token);

        if (! $share || $share->share_type !== Share::TYPE_DIRECTORY) {
            return $this->renderExpiredOrNotFound($token);
        }

        $asset = $this->resolveDirectoryAsset($share, $assetId);
        if (! $asset) {
            return $this->renderNotFound();
        }

        return $this->streamAsset(
            $asset,
            $request->query('disposition', 'attachment'),
            fn () => $this->shareRepository->incrementDownload($share)
        );
    }

    /**
     * Serve a 300px thumbnail for an asset reachable through this share.
     * Mirrors FileController::thumbnail() but scoped strictly to the share.
     */
    public function thumbnail(string $token, int $assetId)
    {
        $share = $this->shareRepository->findActiveByToken($token);

        if (! $share) {
            return abort(404);
        }

        $asset = $this->resolveAssetForShare($share, $assetId);
        if (! $asset) {
            return abort(404);
        }

        $disk = Directory::getAssetDisk();
        $path = $asset->path;

        $coverPath = $asset->file_type === 'audio' ? ($asset->meta_data['cover_art_path'] ?? null) : null;
        if ($coverPath && Storage::disk($disk)->exists($coverPath)) {
            return $this->fileResponse($coverPath);
        }

        if ($asset->file_type === 'video' || strtolower((string) $asset->extension) === 'pdf') {
            $cached = $asset->meta_data['thumbnail_path'] ?? ('thumbnails/'.$path.'.jpg');
            if (Storage::disk($disk)->exists($cached)) {
                return $this->fileResponse($cached);
            }
        }

        $thumbnailPath = 'thumbnails/'.$path;
        if ($this->isImageFile($thumbnailPath, true)) {
            return $this->fileResponse($thumbnailPath);
        }

        if ($this->isImageFile($path)) {
            try {
                $mimeType = Storage::disk($disk)->mimeType($path);
                $image = (new ImageManager(new Driver))
                    ->read(Storage::disk($disk)->get($path))
                    ->scale(width: 300);
                $imageData = $this->encodeImageByExtension($image, $path);
                Storage::disk($disk)->put($thumbnailPath, $imageData);

                return response($imageData, 200)->header('Content-Type', $mimeType);
            } catch (NotReadableException $e) {
                Log::warning('DAM share thumbnail generation failed: '.$e->getMessage(), ['asset' => $asset->id]);
            }
        } elseif ($this->isSvgFile($path)) {
            if (! Storage::disk($disk)->exists($thumbnailPath)) {
                Storage::disk($disk)->copy($path, $thumbnailPath);
            }

            return response(Storage::disk($disk)->get($thumbnailPath), 200)
                ->header('Content-Type', 'image/svg+xml');
        }

        return $this->placeholderResponse($asset);
    }

    /**
     * Stream a file. For S3, redirect to a short-lived presigned URL; for the
     * local/private disk, response()->file() handles range requests so video
     * scrubbing in the public viewer works.
     */
    protected function streamAsset(Asset $asset, string $disposition, ?callable $onSuccess = null)
    {
        $disk = Directory::getAssetDisk();

        if (! Storage::disk($disk)->exists($asset->path)) {
            return abort(404);
        }

        if ($onSuccess && $disposition === 'attachment') {
            $onSuccess();
        }

        $mimeType = Storage::disk($disk)->mimeType($asset->path) ?: 'application/octet-stream';
        $disposition = in_array(strtolower($disposition), ['inline', 'attachment'], true)
            ? strtolower($disposition)
            : 'attachment';

        $filename = $asset->file_name;
        $contentDisposition = $disposition.'; filename="'.addslashes($filename).'"';

        if ($disk === Directory::ASSETS_DISK_AWS) {
            try {
                $url = Storage::disk($disk)->temporaryUrl(
                    $asset->path,
                    now()->addMinutes(10),
                    [
                        'ResponseContentDisposition' => $contentDisposition,
                        'ResponseContentType'        => $mimeType,
                    ]
                );

                return redirect()->away($url);
            } catch (\Throwable $e) {
                Log::error('DAM share S3 stream failed: '.$e->getMessage());

                return abort(500);
            }
        }

        return response()->file(Storage::disk($disk)->path($asset->path), [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => $contentDisposition,
        ]);
    }

    /**
     * Look up an asset that must be a DIRECT child of the share's directory.
     * Subdirectory traversal is rejected with null (caller returns 404).
     */
    protected function resolveDirectoryAsset(Share $share, int $assetId): ?Asset
    {
        return Asset::query()
            ->whereHas('directories', fn ($q) => $q->where('dam_directories.id', $share->target_id))
            ->where('id', $assetId)
            ->first();
    }

    protected function resolveAssetForShare(Share $share, int $assetId): ?Asset
    {
        if ($share->share_type === Share::TYPE_ASSET) {
            return $share->target_id === $assetId ? $share->asset : null;
        }

        return $this->resolveDirectoryAsset($share, $assetId);
    }

    protected function isImageFile(string $path, bool $includeSvg = false): bool
    {
        $disk = Directory::getAssetDisk();
        if (! Storage::disk($disk)->exists($path)) {
            return false;
        }

        $mimeType = Storage::disk($disk)->mimeType($path);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (strtolower($extension) === 'jfif') {
            $mimeType = 'image/jpeg';
        }

        return $includeSvg
            ? Str::startsWith($mimeType, 'image/')
            : Str::startsWith($mimeType, 'image/') && $mimeType !== 'image/svg+xml';
    }

    protected function isSvgFile(string $path): bool
    {
        $disk = Directory::getAssetDisk();

        return Storage::disk($disk)->exists($path)
            && Storage::disk($disk)->mimeType($path) === 'image/svg+xml';
    }

    protected function fileResponse(string $path)
    {
        $disk = Directory::getAssetDisk();

        if ($disk === Directory::ASSETS_DISK_AWS) {
            try {
                $visibility = Storage::disk($disk)->getVisibility($path);
                $url = $visibility === 'public'
                    ? Storage::disk($disk)->url($path)
                    : Storage::disk($disk)->temporaryUrl($path, now()->addMinutes(5));

                return redirect()->away($url);
            } catch (\Throwable $e) {
                return abort(500);
            }
        }

        return response()->file(Storage::disk($disk)->path($path));
    }

    protected function placeholderResponse(Asset $asset)
    {
        $type = $asset->file_type ?: 'unspecified';
        $placeholderPath = 'dam/grid/'.$type.'.svg';

        if (Storage::disk('public')->exists($placeholderPath)) {
            $mimeType = Storage::disk('public')->mimeType($placeholderPath);
            $fileContent = Storage::disk('public')->get($placeholderPath);

            return response($fileContent, 200)->header('Content-Type', $mimeType);
        }

        return response('', 404);
    }

    protected function encodeImageByExtension($image, string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'png'                 => $image->toPng(),
            'webp'                => $image->toWebp(),
            'gif'                 => $image->toGif(),
            'bmp'                 => $image->toBmp(),
            'tiff', 'tif'         => $image->toTiff(),
            'avif'                => $image->toAvif(),
            'jpg', 'jpeg', 'jfif' => $image->toJpeg(),
            default               => $image->toJpeg(),
        };
    }

    protected function renderNotFound()
    {
        return response()->view('dam::share.public.not-found', [], 404);
    }

    protected function renderExpired(Share $share)
    {
        return response()->view('dam::share.public.expired', [
            'share' => $share,
        ], 410);
    }

    /**
     * For non-show endpoints, treat both expired and not-found uniformly so
     * we don't leak whether a token ever existed; show a 404.
     */
    protected function renderExpiredOrNotFound(string $token)
    {
        $share = $this->shareRepository->findByToken($token);
        if ($share && ! $share->isActive()) {
            return $this->renderExpired($share);
        }

        return $this->renderNotFound();
    }
}
