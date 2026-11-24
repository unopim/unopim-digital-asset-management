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
 * Surfaces AI tagging on the core Job Tracker page as a 'system' job.
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
     * Race-safe: concurrent jobs in one upload session share a single job_track row.
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
     * Job track for a tagging run outside any upload session, such as an API upload.
     */
    public function startStandaloneJob(?int $userId): JobTrack
    {
        return $this->createJobTrack($userId);
    }

    /**
     * The tracker's live view counts these rows, so one is created per tagged image.
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
     * Writes to the downloadable job log, since a session job never flips to failed.
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
     * Only a standalone run is marked failed; a session job completes with its invalid rows counted.
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
