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
use Webkul\DAM\Services\AiTaggingJobTrackerService;
use Webkul\DAM\Services\AssetAutoTaggingService;
use Webkul\DAM\Services\MetadataExtractionService;
use Webkul\DAM\Traits\SettlesUploadBatch;

class ProcessAssetUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SettlesUploadBatch;

    public int $timeout = 300;

    public function __construct(
        protected int $assetId,
        protected ?int $batchId = null,
        protected bool $autoTagEligible = false,
        protected ?int $tagBatchId = null,
        protected ?int $trackBatchId = null,
        protected ?int $userId = null,
    ) {
        $this->queue = 'dam-media';
    }

    public function handle(MetadataExtractionService $metadataService, AssetAutoTaggingService $taggingService, AiTaggingJobTrackerService $jobTracker): void
    {
        $batch = $this->batchId ? UploadBatch::find($this->batchId) : null;
        $tracker = $batch?->tracker;

        if ($tracker && $tracker->shouldStop()) {
            return;
        }

        $asset = Asset::find($this->assetId);

        if (! $asset) {
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

            if ($this->tagBatchId) {
                TagAssetWithAi::dispatch($asset->id, $disk, $this->tagBatchId, userId: $this->userId, trackBatchId: $this->trackBatchId);
            } elseif ($this->autoTagEligible && $asset->file_type === 'image' && $taggingService->isEnabled()) {
                $this->dispatchTaggingJob($asset, $disk, $tracker, $jobTracker);
            }

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
     * A tagging batch is only created once tagging is actually queued (not upfront
     * with the upload batch), so total_files stays accurate for files that never
     * become tagging-eligible. It joins the same tracker session as the upload.
     *
     * The job_track_batches row is created here too — at dispatch time, not when
     * the (rate-limited, possibly much later) TagAssetWithAi job actually runs —
     * so the live tracker view's total reflects every queued image immediately
     * instead of growing one row at a time as each tagging job starts.
     */
    protected function dispatchTaggingJob(Asset $asset, string $disk, ?UploadTracker $tracker, AiTaggingJobTrackerService $jobTracker): void
    {
        $tagBatchId = null;
        $trackBatchId = null;

        if ($tracker) {
            $jobTrack = $jobTracker->ensureSessionJob($tracker);

            $tagBatchId = UploadBatch::create([
                'upload_tracker_id' => $tracker->id,
                'asset_id'          => $asset->id,
                'state'             => UploadBatch::STATE_PENDING,
            ])->id;

            UploadTracker::whereKey($tracker->id)->increment('total_files');

            if ($jobTrack) {
                $trackBatchId = $jobTracker->startBatch($jobTrack)->id;
            }
        }

        TagAssetWithAi::dispatch($asset->id, $disk, $tagBatchId, userId: $this->userId, trackBatchId: $trackBatchId);
    }
}
