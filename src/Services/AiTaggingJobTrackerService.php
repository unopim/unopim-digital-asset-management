<?php

declare(strict_types=1);

namespace Webkul\DAM\Services;

use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\UploadTracker;
use Webkul\DataTransfer\Helpers\AbstractJob;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\DataTransfer\Services\JobLogger;

/**
 * Surfaces AI tagging activity on the core "Job Tracker" admin page
 * (data_transfer job_track/job_instances) as a 'system'-type job, entirely
 * from DAM — it only calls DataTransfer's existing repositories/models.
 */
class AiTaggingJobTrackerService
{
    protected const JOB_INSTANCE_CODE = 'dam_ai_tagging';

    public function __construct(
        protected JobInstancesRepository $jobInstancesRepository,
        protected JobTrackRepository $jobTrackRepository,
        protected JobTrackBatchRepository $jobTrackBatchRepository,
    ) {}

    /**
     * One job_track row aggregates every image tagged during an upload
     * session, created lazily on the first tagging batch. Race-safe across
     * concurrent ProcessAssetUpload jobs in the same session.
     */
    public function ensureSessionJob(UploadTracker $tracker): ?JobTrack
    {
        if ($tracker->job_track_id) {
            return JobTrack::find($tracker->job_track_id);
        }

        $jobTrack = $this->createJobTrack($tracker->user_id);

        $claimed = UploadTracker::whereKey($tracker->id)
            ->whereNull('job_track_id')
            ->update(['job_track_id' => $jobTrack->id]);

        if (! $claimed) {
            $this->jobTrackRepository->delete($jobTrack->id);

            return JobTrack::find($tracker->fresh()?->job_track_id);
        }

        $tracker->job_track_id = $jobTrack->id;

        return $jobTrack;
    }

    /**
     * A tagging run outside any upload session (e.g. an API upload) gets its
     * own standalone job_track, created and completed within the same job.
     */
    public function startStandaloneJob(?int $userId): JobTrack
    {
        return $this->createJobTrack($userId);
    }

    /**
     * One job_track_batches row per tagged image — the live "processing" view
     * (Import::stats()) is driven entirely by this table's row count/state,
     * with no fallback to job_track.processed_rows_count.
     */
    public function startBatch(JobTrack $jobTrack): JobTrackBatch
    {
        return $this->jobTrackBatchRepository->create([
            'job_track_id' => $jobTrack->id,
            'data'         => [],
            'state'        => AbstractJob::STATE_PENDING,
        ]);
    }

    public function completeBatch(JobTrackBatch $batch, bool $success): void
    {
        $this->jobTrackBatchRepository->update([
            'state'   => AbstractJob::STATE_PROCESSED,
            'summary' => ['created' => 0, 'updated' => $success ? 1 : 0, 'deleted' => 0],
        ], $batch->id);
    }

    /**
     * Session jobs never fail as a whole (a few bad images shouldn't red-flag
     * a 20-image upload), so a per-image failure would otherwise vanish behind
     * invalid_rows_count with no detail. This writes it to the same downloadable
     * job log BulkProductUpdate uses — the tracker's "Download Log" button
     * already renders for any job in the completed/failed state, gated only
     * on state, not job type, so no view change is needed to expose it.
     */
    public function logFailure(JobTrack $jobTrack, int $assetId, string $message): void
    {
        JobLogger::make($jobTrack->id)->error("Asset {$assetId}: {$message}");
    }

    public function recordProgress(JobTrack $jobTrack, bool $success): void
    {
        DB::table('job_track')->where('id', $jobTrack->id)->increment(
            $success ? 'processed_rows_count' : 'invalid_rows_count',
            1,
            ['state' => AbstractJob::STATE_PROCESSING]
        );
    }

    /**
     * $failed flips the whole job to the tracker's dedicated failed state (its
     * own view, distinct from "completed") — only meaningful for a standalone,
     * single-asset job, where the run's outcome IS the job's outcome. A
     * session job stays "completed" even with some invalid_rows_count, same
     * as a real import completing with a few invalid rows.
     *
     * The tracker's completed-state view only reads summary.created/updated/
     * deleted (shared with import/export jobs) — tagged assets are reported
     * as "updated"; failures stay visible via invalid_rows_count on the grid.
     */
    public function complete(JobTrack $jobTrack, bool $failed = false, ?string $errorMessage = null): void
    {
        $jobTrack->refresh();

        $this->jobTrackRepository->update([
            'state'        => $failed ? AbstractJob::STATE_FAILED : AbstractJob::STATE_COMPLETED,
            'completed_at' => now(),
            'errors'       => $errorMessage ? [$errorMessage] : [],
            'summary'      => [
                'created' => 0,
                'updated' => $jobTrack->processed_rows_count,
                'deleted' => 0,
            ],
        ], $jobTrack->id);
    }

    protected function createJobTrack(?int $userId): JobTrack
    {
        $jobInstance = $this->jobInstancesRepository->findOneByField('code', self::JOB_INSTANCE_CODE)
            ?? $this->jobInstancesRepository->create([
                'type'                  => 'system',
                'action'                => 'tag',
                'code'                  => self::JOB_INSTANCE_CODE,
                'entity_type'           => 'dam_asset',
                'validation_strategy'   => 'skip-errors',
                'allowed_errors'        => 0,
                'field_separator'       => null,
                'file_path'             => null,
                'images_directory_path' => null,
                'filters'               => null,
            ]);

        return $this->jobTrackRepository->create([
            'state'               => AbstractJob::STATE_PENDING,
            'type'                => $jobInstance->type,
            'action'              => $jobInstance->action,
            'validation_strategy' => $jobInstance->validation_strategy,
            'allowed_errors'      => $jobInstance->allowed_errors,
            'field_separator'     => $jobInstance->field_separator,
            'file_path'           => $jobInstance->file_path,
            'meta'                => $jobInstance->toArray(),
            'job_instances_id'    => $jobInstance->id,
            'user_id'             => $userId,
            'started_at'          => now(),
        ]);
    }
}
