<?php

declare(strict_types=1);

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\UploadBatch;
use Webkul\DAM\Services\AiTaggingJobTrackerService;
use Webkul\DAM\Services\AssetAutoTaggingService;
use Webkul\DAM\Traits\SettlesUploadBatch;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;

/**
 * Runs the AI tagging call on its own queue lifecycle, separate from
 * ProcessAssetUpload, so a slow/failed AI call can't delay metadata
 * extraction, thumbnail generation, or upload-batch completion.
 */
class TagAssetWithAi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SettlesUploadBatch;

    public int $timeout = 180;

    public int $tries = 2;

    public function __construct(
        protected int $assetId,
        protected string $disk,
        protected ?int $batchId = null,
        protected ?int $userId = null,
        protected ?int $trackBatchId = null,
    ) {
        $this->queue = 'dam-media';
    }

    /**
     * @return array<int, RateLimited>
     */
    public function middleware(): array
    {
        return [new RateLimited('dam-ai-tagging')];
    }

    public function handle(AssetAutoTaggingService $service, AiTaggingJobTrackerService $jobTracker): void
    {
        $batch = $this->batchId ? UploadBatch::find($this->batchId) : null;
        $tracker = $batch?->tracker;

        $asset = Asset::find($this->assetId);

        if (! $asset) {
            $this->settleBatch($batch, $tracker, failed: false);

            return;
        }

        $batch?->update(['state' => UploadBatch::STATE_PROCESSING]);

        // No tracker session to aggregate under (e.g. an API upload) — track this run on its own.
        $jobTrack = $tracker
            ? ($tracker->job_track_id ? JobTrack::find($tracker->job_track_id) : null)
            : $jobTracker->startStandaloneJob($this->userId);

        $trackBatch = $this->trackBatchId
            ? JobTrackBatch::find($this->trackBatchId)
            : ($jobTrack ? $jobTracker->startBatch($jobTrack) : null);

        $success = $service->tagAsset($asset, $this->disk);

        if ($jobTrack) {
            $jobTracker->recordProgress($jobTrack, $success);

            if ($trackBatch) {
                $jobTracker->completeBatch($trackBatch, $success);
            }

            if (! $success) {
                $jobTracker->logFailure($jobTrack, $this->assetId, $service->lastError() ?? 'AI tagging failed.');
            }

            if (! $tracker) {
                $jobTracker->complete($jobTrack->fresh(), failed: ! $success, errorMessage: $service->lastError());
            }
        }

        $this->settleBatch($batch, $tracker, failed: ! $success, error: $success ? null : 'AI tagging failed.');
    }
}
