<?php

declare(strict_types=1);

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\UploadBatch;
use Webkul\DAM\Models\UploadTracker;
use Webkul\DAM\Services\AiTaggingJobTrackerService;
use Webkul\DAM\Services\AssetAutoTaggingService;
use Webkul\DAM\Traits\SettlesUploadBatch;
use Webkul\DataTransfer\Helpers\AbstractJob;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;

/**
 * Runs separately from ProcessAssetUpload so a slow AI call cannot delay upload finalisation.
 */
class TagAssetWithAi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SettlesUploadBatch;

    public int $timeout = 180;

    public int $tries = 2;

    protected const FAILURE_MESSAGE = 'AI tagging failed.';

    protected ?string $pendingClaim = null;

    public function __construct(
        protected int $assetId,
        protected string $disk,
        protected ?int $batchId = null,
        protected ?int $userId = null,
        protected ?int $trackBatchId = null,
    ) {
        $this->queue = config('dam.queues.media');

        $this->pendingClaim = app(AssetAutoTaggingService::class)->markPending($assetId);
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
            $service->clearPending($this->assetId, $this->pendingClaim);

            $this->settleBatch($batch, $tracker, failed: false);

            return;
        }

        $batch?->update(['state' => UploadBatch::STATE_PROCESSING]);

        [$jobTrack, $trackBatch] = $this->resolveTracking($tracker, $jobTracker);

        $success = $service->tagAsset($asset, $this->disk);

        $service->clearPending($this->assetId, $this->pendingClaim);

        if ($jobTrack) {
            $jobTracker->recordProgress($jobTrack, $success);

            if ($trackBatch) {
                $jobTracker->completeBatch($trackBatch, $success);
            }

            if (! $success) {
                $jobTracker->logFailure($jobTrack, $this->assetId, $service->lastError() ?? self::FAILURE_MESSAGE);
            }

            if (! $tracker) {
                $jobTracker->complete($jobTrack->fresh(), failed: ! $success, errorMessage: $service->lastError());
            }
        }

        $this->settleBatch($batch, $tracker, failed: ! $success, error: $success ? null : self::FAILURE_MESSAGE);

        $this->forgetCreatedTracking();
    }

    /**
     * Settles what handle() left open after a timeout or exhausted retries; records already settled are left alone.
     */
    public function failed(?\Throwable $exception): void
    {
        app(AssetAutoTaggingService::class)->clearPending($this->assetId, $this->pendingClaim);

        $error = $exception?->getMessage() ?: self::FAILURE_MESSAGE;

        $created = $this->createdTracking();

        $this->failOpenTrackBatch($this->trackBatchId ?? $created['track_batch_id'] ?? null, $error);
        $this->failOpenStandaloneJob($created['job_track_id'] ?? null, $error);

        $batch = $this->batchId ? UploadBatch::find($this->batchId) : null;

        if ($batch && in_array($batch->state, [UploadBatch::STATE_PENDING, UploadBatch::STATE_PROCESSING], true)) {
            $this->settleBatch($batch, $batch->tracker, failed: true, error: $error);
        }

        $this->forgetCreatedTracking();
    }

    /**
     * Tracking created here is cached under the claim token, so a retry reuses it and failed() can settle it.
     *
     * @return array{0: ?JobTrack, 1: ?JobTrackBatch}
     */
    protected function resolveTracking(?UploadTracker $tracker, AiTaggingJobTrackerService $jobTracker): array
    {
        $created = $this->createdTracking();

        if ($tracker) {
            $jobTrack = $tracker->job_track_id ? JobTrack::find($tracker->job_track_id) : null;
        } else {
            $jobTrack = (isset($created['job_track_id']) ? JobTrack::find($created['job_track_id']) : null)
                ?? $jobTracker->startStandaloneJob($this->userId);
        }

        if ($this->trackBatchId) {
            $trackBatch = JobTrackBatch::find($this->trackBatchId);
        } else {
            $trackBatch = (isset($created['track_batch_id']) ? JobTrackBatch::find($created['track_batch_id']) : null)
                ?? ($jobTrack ? $jobTracker->startBatch($jobTrack) : null);
        }

        $this->rememberCreatedTracking([
            'job_track_id'   => $tracker ? null : $jobTrack?->id,
            'track_batch_id' => $this->trackBatchId ? null : $trackBatch?->id,
        ]);

        return [$jobTrack, $trackBatch];
    }

    protected function failOpenStandaloneJob(?int $jobTrackId, string $error): void
    {
        $jobTrack = $jobTrackId ? JobTrack::find($jobTrackId) : null;

        if (! $jobTrack || in_array($jobTrack->state, [AbstractJob::STATE_COMPLETED, AbstractJob::STATE_FAILED], true)) {
            return;
        }

        app(AiTaggingJobTrackerService::class)->complete($jobTrack, failed: true, errorMessage: $error);
    }

    protected function failOpenTrackBatch(?int $trackBatchId, string $error): void
    {
        $trackBatch = $trackBatchId ? JobTrackBatch::find($trackBatchId) : null;

        if (! $trackBatch || ! in_array($trackBatch->state, [AbstractJob::STATE_PENDING, AbstractJob::STATE_PROCESSING], true)) {
            return;
        }

        $jobTracker = app(AiTaggingJobTrackerService::class);

        $jobTracker->completeBatch($trackBatch, false);

        if ($jobTrack = JobTrack::find($trackBatch->job_track_id)) {
            $jobTracker->recordProgress($jobTrack, false);
            $jobTracker->logFailure($jobTrack, $this->assetId, $error);
        }
    }

    /**
     * @return array{job_track_id?: ?int, track_batch_id?: ?int}
     */
    protected function createdTracking(): array
    {
        $key = $this->createdTrackingKey();

        return $key ? (array) Cache::get($key, []) : [];
    }

    /**
     * @param  array{job_track_id: ?int, track_batch_id: ?int}  $tracking
     */
    protected function rememberCreatedTracking(array $tracking): void
    {
        if (($key = $this->createdTrackingKey()) && array_filter($tracking) !== []) {
            Cache::put($key, $tracking, now()->addDay());
        }
    }

    protected function forgetCreatedTracking(): void
    {
        if ($key = $this->createdTrackingKey()) {
            Cache::forget($key);
        }
    }

    protected function createdTrackingKey(): ?string
    {
        return $this->pendingClaim ? "dam.ai_tagging.tracking.{$this->pendingClaim}" : null;
    }
}
