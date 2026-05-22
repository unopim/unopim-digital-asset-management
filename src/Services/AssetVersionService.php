<?php

namespace Webkul\DAM\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\DAM\Exceptions\AssetVersionMissingException;
use Webkul\DAM\Jobs\GeneratePdfThumbnail;
use Webkul\DAM\Jobs\GenerateVideoThumbnail;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetVersion;
use Webkul\DAM\Models\Directory;

class AssetVersionService
{
    public function __construct(protected HistoryThumbnailGenerator $thumbGenerator) {}

    /**
     * Snapshot the currently-live binary of $asset into versions/{id}/last.{ext}
     * and upsert a single AssetVersion row.
     *
     * Cheap and safe to call: silently no-ops if the live file is missing.
     */
    public function backup(Asset $asset): void
    {
        $disk = Directory::getAssetDisk();
        $path = $asset->path;

        if (! $path || Storage::disk($disk)->missing($path)) {
            return;
        }

        $extension = $asset->extension
            ?: (pathinfo($path, PATHINFO_EXTENSION) ?: 'bin');

        $versionPath = "versions/{$asset->id}/last.{$extension}";

        Storage::disk($disk)->delete($versionPath);
        Storage::disk($disk)->copy($path, $versionPath);

        // Bust any cached OLD-side history thumbnail; the next history-modal view
        // will regenerate it from this fresh version_path.
        Storage::disk($disk)->delete('versions/thumbnails/'.$asset->id.'/last.jpg');
        Storage::disk($disk)->delete('versions/thumbnails/'.$asset->id.'/last.svg');

        AssetVersion::updateOrCreate(
            ['asset_id' => $asset->id],
            [
                'version_path'       => $versionPath,
                'original_path'      => $path,
                'original_file_name' => $asset->file_name ?: basename($path),
                'original_extension' => $extension,
                'original_mime_type' => $asset->mime_type ?: 'application/octet-stream',
                'original_file_type' => $asset->file_type,
                'original_file_size' => (int) ($asset->file_size ?: 0),
                'original_meta_data' => $asset->meta_data,
            ],
        );

        // Pre-warm the OLD-side history thumbnail so the next modal open is an
        // instant cache hit, never waits on ffmpeg / pdftoppm.
        $this->pregenerateHistoryThumbnail($asset, $versionPath, $extension, $disk);
    }

    /**
     * Eagerly generate the cached history-modal OLD-side thumbnail for this
     * asset's most recent backup. Best-effort: any failure is swallowed and
     * the lazy on-demand path in `HistoryRestoreController::thumbnail` still
     * works as a fallback.
     */
    protected function pregenerateHistoryThumbnail(Asset $asset, string $versionPath, string $extension, string $disk): void
    {
        @set_time_limit(0);

        $mime = (string) ($asset->mime_type ?? '');
        $ext = strtolower($extension);
        $assetId = $asset->id;

        try {
            if ($mime === 'image/svg+xml' || $ext === 'svg') {
                $this->thumbGenerator->fromSvg(
                    sourcePath: $versionPath,
                    destPath: 'versions/thumbnails/'.$assetId.'/last.svg',
                    disk: $disk,
                );
            } elseif (Str::startsWith($mime, 'image/')) {
                $this->thumbGenerator->fromImage(
                    sourcePath: $versionPath,
                    destPath: 'versions/thumbnails/'.$assetId.'/last.jpg',
                    width: 300,
                    disk: $disk,
                );
            } elseif ($ext === 'pdf' || $mime === 'application/pdf') {
                $this->thumbGenerator->fromPdf(
                    sourcePath: $versionPath,
                    destPath: 'versions/thumbnails/'.$assetId.'/last.jpg',
                    width: 300,
                    disk: $disk,
                );
            } elseif (Str::startsWith($mime, 'video/') || in_array($ext, ['mp4', 'mkv', 'avi', 'mov', 'flv', 'webm', 'ogv'], true)) {
                $this->thumbGenerator->fromVideo(
                    sourcePath: $versionPath,
                    destPath: 'versions/thumbnails/'.$assetId.'/last.jpg',
                    width: 300,
                    disk: $disk,
                );
            }
        } catch (\Throwable $e) {
            Log::warning('DAM history: pre-warm thumbnail failed.', [
                'asset'   => $assetId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Restore the most recent backup back to the live asset path.
     *
     * Snapshots the currently-live binary first so a forward-undo is possible.
     */
    public function restore(int $assetId): Asset
    {
        return DB::transaction(function () use ($assetId) {
            $asset = Asset::lockForUpdate()->findOrFail($assetId);

            $version = AssetVersion::where('asset_id', $assetId)->first();
            if (! $version) {
                throw new AssetVersionMissingException("No backup found for asset {$assetId}");
            }

            $disk = Directory::getAssetDisk();
            if (Storage::disk($disk)->missing($version->version_path)) {
                throw new AssetVersionMissingException(
                    "Version binary missing on disk for asset {$assetId}"
                );
            }

            $targetPath = $version->original_path;
            $targetFileName = $version->original_file_name;
            $targetExtension = $version->original_extension;
            $targetMime = $version->original_mime_type;
            $targetType = $version->original_file_type;
            $targetSize = $version->original_file_size;
            $targetMetaData = $version->original_meta_data;
            $binary = Storage::disk($disk)->get($version->version_path);

            // Snapshot path BEFORE backup() so we can purge its cache after the swap.
            $previouslyLivePath = $asset->path;

            $this->backup($asset);

            Storage::disk($disk)->delete($targetPath);
            Storage::disk($disk)->put($targetPath, $binary);

            $payload = [
                'path'      => $targetPath,
                'file_name' => $targetFileName,
                'extension' => $targetExtension,
                'mime_type' => $targetMime,
                'file_size' => $targetSize,
            ];

            // file_type is required for the renderer + thumbnail jobs to branch correctly.
            // Older backup rows (created before original_file_type was added) won't have it,
            // so fall back to deriving from mime/extension.
            $payload['file_type'] = $targetType ?: $this->deriveFileType($targetMime, $targetExtension);

            if ($targetMetaData !== null) {
                $payload['meta_data'] = $targetMetaData;
            }

            $asset->update($payload);

            // Clear cached preview/thumbnail entries only for the path that's no longer
            // live. The restored target path's cached binary (if it still exists) is the
            // exact binary we just put back, so it remains valid; if it's missing the
            // thumbnail endpoint will lazy-generate on first request.
            $this->clearAssetCache($previouslyLivePath, $asset->id);

            $fresh = $asset->fresh();

            // Eagerly regenerate the thumbnail JPG for videos / PDFs so the
            // grid + preview show the right image immediately, without depending
            // on a lazy first-request regen (which can silently fail for tiny or
            // edge-case binaries).
            $this->regenerateThumbnailIfApplicable($fresh);

            return $fresh->fresh();
        });
    }

    /**
     * For video / PDF assets, dispatch the appropriate thumbnail job synchronously.
     * Safe to call after restore — the job is a no-op if the asset isn't actually
     * the expected file_type / extension, and any ffmpeg / pdftoppm error is
     * logged and swallowed so a single bad binary can't break the restore flow.
     */
    protected function regenerateThumbnailIfApplicable(Asset $asset): void
    {
        // Same reason as in HistoryThumbnailGenerator — ffmpeg / pdftoppm on
        // big binaries would otherwise be killed by PHP's default 30s web
        // request cap mid-encode.
        @set_time_limit(0);

        try {
            if ($asset->file_type === 'video') {
                dispatch_sync(new GenerateVideoThumbnail($asset->id));
            } elseif (strtolower((string) $asset->extension) === 'pdf') {
                dispatch_sync(new GeneratePdfThumbnail($asset->id));
            }
        } catch (\Throwable $e) {
            Log::warning('DAM history restore: thumbnail regeneration failed.', [
                'asset'   => $asset->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Derive the Asset::$file_type bucket from a mime type, falling back to the
     * extension when mime is missing. Mirrors the buckets used by
     * AssetHelper::getFileType() so the restored asset routes through the
     * matching renderer + thumbnail job.
     */
    protected function deriveFileType(?string $mime, ?string $extension): string
    {
        $mime = (string) $mime;
        if (str_contains($mime, 'image')) {
            return 'image';
        }
        if (str_contains($mime, 'video')) {
            return 'video';
        }
        if (str_contains($mime, 'audio')) {
            return 'audio';
        }

        $ext = strtolower((string) $extension);
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'bmp', 'webp', 'tiff', 'tif', 'jfif'], true)) {
            return 'image';
        }
        if (in_array($ext, ['mp4', 'mkv', 'avi', 'mov', 'flv', 'webm', 'ogv'], true)) {
            return 'video';
        }
        if (in_array($ext, ['mp3', 'wav', 'aac', 'flac', 'ogg', 'm4a'], true)) {
            return 'audio';
        }

        return 'document';
    }

    /**
     * Mirror of AssetController::clearAssetCache — wipes the stale thumbnail
     * and preview entries so the post-restore binary's first request triggers
     * a fresh generation (image resize / PDF first-page / video first-frame).
     */
    protected function clearAssetCache(?string $path, int $assetId): void
    {
        if (! $path) {
            return;
        }

        $disk = Directory::getAssetDisk();

        Storage::disk($disk)->delete('thumbnails/'.$path);
        Storage::disk($disk)->delete('thumbnails/'.$path.'.jpg');

        foreach (Storage::disk($disk)->allFiles('preview') as $previewFile) {
            if (str_ends_with($previewFile, '/'.$path)) {
                Storage::disk($disk)->delete($previewFile);
            }
        }

        foreach (['jpg', 'png', 'gif', 'webp'] as $ext) {
            Storage::disk($disk)->delete('covers/'.$assetId.'.'.$ext);
        }
    }
}
