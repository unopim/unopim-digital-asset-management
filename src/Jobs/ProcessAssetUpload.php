<?php

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\UploadBatch;
use Webkul\DAM\Models\UploadTracker;
use Webkul\DAM\Services\MetadataExtractionService;

/**
 * Finalises a freshly-uploaded asset in the background: the expensive metadata
 * extraction (exiftool / ffprobe), audio cover-art and thumbnail generation are
 * lifted out of the HTTP request so the upload itself stays fast and low-memory.
 *
 * When the asset belongs to an upload session ($batchId set) the job honours the
 * session's cancel / pause state exactly like the core DataTransfer batch jobs:
 * a paused or cancelled tracker makes the job abort without touching the asset.
 */
class ProcessAssetUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    /**
     * @param  int  $assetId  The asset row to finalise (already persisted).
     * @param  int|null  $batchId  The dam_upload_batches row, when part of a session.
     */
    public function __construct(
        protected int $assetId,
        protected ?int $batchId = null,
    ) {}

    public function handle(MetadataExtractionService $metadataService): void
    {
        $batch = $this->batchId ? UploadBatch::find($this->batchId) : null;
        $tracker = $batch?->tracker;

        // Session paused/cancelled: leave the batch pending so a resume can
        // re-dispatch it, and do no work. Mirrors AbstractImporter::shouldStop().
        if ($tracker && $tracker->shouldStop()) {
            return;
        }

        $asset = Asset::find($this->assetId);

        if (! $asset) {
            // Asset was deleted before finalisation — nothing left to do.
            $this->settleBatch($batch, $tracker, failed: false);

            return;
        }

        $batch?->update(['state' => UploadBatch::STATE_PROCESSING]);

        try {
            $disk = Directory::getAssetDisk();

            $metaData = $this->extractMetadata($metadataService, $asset, $disk);

            if (! empty($metaData)) {
                $asset->update(['meta_data' => $metaData]);
            }

            $this->attachAudioCoverArt($metadataService, $asset, $metaData, $disk);

            $this->dispatchThumbnailJob($asset);

            $this->settleBatch($batch, $tracker, failed: false);
        } catch (\Throwable $e) {
            Log::warning('DAM asset finalisation failed.', [
                'asset'   => $this->assetId,
                'batch'   => $this->batchId,
                'message' => $e->getMessage(),
            ]);

            $this->settleBatch($batch, $tracker, failed: true, error: $e->getMessage());
        }
    }

    /**
     * Extract metadata from the stored (not the temporary request) file so the
     * job is self-contained. Reads through the asset disk — a real local path
     * for local/private disks, a downloaded temp copy for S3.
     */
    protected function extractMetadata(MetadataExtractionService $service, Asset $asset, string $disk): array
    {
        if ($disk === Directory::ASSETS_DISK_AWS) {
            return $service->extractMetadata(
                $asset->path,
                disk: Directory::ASSETS_DISK_AWS,
                originalFileName: $asset->file_name,
            );
        }

        $absolutePath = Storage::disk($disk)->path($asset->path);

        return $service->extractMetadata(
            $absolutePath,
            disk: 'local',
            localPath: $absolutePath,
            originalFileName: $asset->file_name,
        );
    }

    /**
     * For audio assets, pull embedded cover art and persist its path onto the
     * asset's meta_data. No-op for non-audio types or when no artwork is found.
     */
    protected function attachAudioCoverArt(MetadataExtractionService $service, Asset $asset, array $metaData, string $disk): void
    {
        if (! str_starts_with((string) $asset->mime_type, 'audio/')) {
            return;
        }

        $isS3 = $disk === Directory::ASSETS_DISK_AWS;
        $localPath = $isS3
            ? $service->getFileTempPath($asset->path, Directory::ASSETS_DISK_AWS)
            : Storage::disk($disk)->path($asset->path);

        if (! $localPath || ! file_exists($localPath)) {
            return;
        }

        try {
            $coverData = $service->extractCoverArtData($localPath);

            if (! $coverData) {
                return;
            }

            $coverPath = $service->storeCoverArt($coverData, $asset->id, $disk);

            if ($coverPath) {
                $asset->update(['meta_data' => array_merge($metaData, ['cover_art_path' => $coverPath])]);
            }
        } finally {
            if ($isS3 && $localPath && file_exists($localPath)) {
                @unlink($localPath);
            }
        }
    }

    /**
     * Queue a thumbnail generation job for PDF/video assets.
     */
    protected function dispatchThumbnailJob(Asset $asset): void
    {
        if ($asset->file_type === 'video') {
            GenerateVideoThumbnail::dispatch($asset->id);

            return;
        }

        if (strtolower((string) $asset->extension) === 'pdf') {
            GeneratePdfThumbnail::dispatch($asset->id);
        }
    }

    /**
     * Record the batch outcome, bump the tracker counters atomically and mark
     * the session completed once every file has settled.
     */
    protected function settleBatch(?UploadBatch $batch, ?UploadTracker $tracker, bool $failed, ?string $error = null): void
    {
        if (! $batch) {
            return;
        }

        $batch->update([
            'state' => $failed ? UploadBatch::STATE_FAILED : UploadBatch::STATE_PROCESSED,
            'error' => $failed ? $error : null,
        ]);

        if (! $tracker) {
            return;
        }

        UploadTracker::whereKey($tracker->id)
            ->increment($failed ? 'failed_files' : 'processed_files');

        $this->finalizeTrackerIfDone($tracker->id);
    }

    /**
     * Flip the tracker to `completed` once processed + failed reaches the total
     * and nothing is still pending. Reloads a fresh row to avoid stale counters
     * under concurrent workers.
     */
    protected function finalizeTrackerIfDone(int $trackerId): void
    {
        $tracker = UploadTracker::find($trackerId);

        if (! $tracker || ! in_array($tracker->state, [UploadTracker::STATE_PENDING, UploadTracker::STATE_PROCESSING], true)) {
            return;
        }

        $settled = $tracker->processed_files + $tracker->failed_files;

        if ($tracker->total_files <= 0 || $settled < $tracker->total_files) {
            return;
        }

        $stillOpen = $tracker->batches()
            ->whereIn('state', [UploadBatch::STATE_PENDING, UploadBatch::STATE_PROCESSING])
            ->exists();

        if ($stillOpen) {
            return;
        }

        $tracker->update([
            'state'        => UploadTracker::STATE_COMPLETED,
            'completed_at' => now(),
        ]);
    }
}
