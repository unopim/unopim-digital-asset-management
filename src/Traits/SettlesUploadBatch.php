<?php

namespace Webkul\DAM\Traits;

use Webkul\DAM\Models\UploadBatch;
use Webkul\DAM\Models\UploadTracker;
use Webkul\DAM\Services\AiTaggingJobTrackerService;
use Webkul\DataTransfer\Models\JobTrack;

trait SettlesUploadBatch
{
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

        if ($tracker->job_track_id && ($jobTrack = JobTrack::find($tracker->job_track_id))) {
            app(AiTaggingJobTrackerService::class)->complete($jobTrack);
        }
    }
}
