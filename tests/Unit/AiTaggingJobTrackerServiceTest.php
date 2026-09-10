<?php

use Illuminate\Support\Str;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\UploadTracker;
use Webkul\DAM\Services\AiTaggingJobTrackerService;
use Webkul\DataTransfer\Helpers\AbstractJob;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;

function makeUploadTracker(): UploadTracker
{
    return UploadTracker::create([
        'uuid'         => (string) Str::uuid(),
        'directory_id' => Directory::factory()->create()->id,
        'user_id'      => 1,
        'state'        => UploadTracker::STATE_PROCESSING,
        'total_files'  => 1,
        'started_at'   => now(),
    ]);
}

it('creates a system job_instance once and reuses it across sessions', function () {
    $service = app(AiTaggingJobTrackerService::class);

    $service->ensureSessionJob(makeUploadTracker());
    $service->ensureSessionJob(makeUploadTracker());

    $instances = JobInstances::where('code', 'dam_ai_tagging')->get();

    expect($instances)->toHaveCount(1);
    expect($instances->first()->type)->toBe('system');
});

it('creates a job_track row linked to the tracker session', function () {
    $tracker = makeUploadTracker();

    $jobTrack = app(AiTaggingJobTrackerService::class)->ensureSessionJob($tracker);

    expect($jobTrack)->not->toBeNull();
    expect($jobTrack->type)->toBe('system');
    expect($jobTrack->state)->toBe(AbstractJob::STATE_PENDING);
    expect($jobTrack->user_id)->toBe($tracker->user_id);
    expect($tracker->fresh()->job_track_id)->toBe($jobTrack->id);
});

it('reuses the same job_track for repeated calls on the same session', function () {
    $tracker = makeUploadTracker();
    $service = app(AiTaggingJobTrackerService::class);

    $first = $service->ensureSessionJob($tracker);
    $second = $service->ensureSessionJob($tracker->fresh());

    expect($second->id)->toBe($first->id);
});

it('records progress with atomic counters and marks the job processing', function () {
    $tracker = makeUploadTracker();
    $service = app(AiTaggingJobTrackerService::class);
    $jobTrack = $service->ensureSessionJob($tracker);

    $service->recordProgress($jobTrack, success: true);
    $service->recordProgress($jobTrack->fresh(), success: false);

    $jobTrack->refresh();
    expect($jobTrack->processed_rows_count)->toBe(1);
    expect($jobTrack->invalid_rows_count)->toBe(1);
    expect($jobTrack->state)->toBe(AbstractJob::STATE_PROCESSING);
});

it('completes a job_track with a summary the tracker\'s completed view actually reads', function () {
    $tracker = makeUploadTracker();
    $service = app(AiTaggingJobTrackerService::class);
    $jobTrack = $service->ensureSessionJob($tracker);

    $service->recordProgress($jobTrack, success: true);
    $service->complete($jobTrack->fresh());

    $jobTrack->refresh();
    expect($jobTrack->state)->toBe(AbstractJob::STATE_COMPLETED);
    expect($jobTrack->completed_at)->not->toBeNull();
    expect($jobTrack->summary)->toBe(['created' => 0, 'updated' => 1, 'deleted' => 0]);
});

it('flips a standalone job to the failed state with the error message, instead of a misleading "completed"', function () {
    $jobTrack = app(AiTaggingJobTrackerService::class)->startStandaloneJob(userId: 1);

    app(AiTaggingJobTrackerService::class)->recordProgress($jobTrack, success: false);
    app(AiTaggingJobTrackerService::class)->complete($jobTrack->fresh(), failed: true, errorMessage: 'provider timeout');

    $jobTrack->refresh();
    expect($jobTrack->state)->toBe(AbstractJob::STATE_FAILED);
    expect($jobTrack->errors)->toBe(['provider timeout']);
});

it('starts a job_track_batches row per tagged image so the live view has something to count', function () {
    $tracker = makeUploadTracker();
    $service = app(AiTaggingJobTrackerService::class);
    $jobTrack = $service->ensureSessionJob($tracker);

    $trackBatch = $service->startBatch($jobTrack);

    expect($trackBatch)->toBeInstanceOf(JobTrackBatch::class);
    expect($trackBatch->job_track_id)->toBe($jobTrack->id);
    expect($trackBatch->state)->toBe(AbstractJob::STATE_PENDING);
    expect($trackBatch->data)->toBe([]);
});

it('completes a job_track_batches row with the summary keys the tracker view reads', function () {
    $tracker = makeUploadTracker();
    $service = app(AiTaggingJobTrackerService::class);
    $jobTrack = $service->ensureSessionJob($tracker);
    $trackBatch = $service->startBatch($jobTrack);

    $service->completeBatch($trackBatch, success: true);

    $trackBatch->refresh();
    expect($trackBatch->state)->toBe(AbstractJob::STATE_PROCESSED);
    expect($trackBatch->summary)->toBe(['created' => 0, 'updated' => 1, 'deleted' => 0]);
});

it('completes a failed job_track_batches row with zero in the updated count', function () {
    $tracker = makeUploadTracker();
    $service = app(AiTaggingJobTrackerService::class);
    $jobTrack = $service->ensureSessionJob($tracker);
    $trackBatch = $service->startBatch($jobTrack);

    $service->completeBatch($trackBatch, success: false);

    expect($trackBatch->fresh()->summary)->toBe(['created' => 0, 'updated' => 0, 'deleted' => 0]);
});

it('gives a standalone job a fresh job_track every time, not reused', function () {
    $service = app(AiTaggingJobTrackerService::class);

    $first = $service->startStandaloneJob(userId: 1);
    $second = $service->startStandaloneJob(userId: 1);

    expect($first->id)->not->toBe($second->id);
    expect(JobTrack::find($first->id))->not->toBeNull();
    expect(JobTrack::find($second->id))->not->toBeNull();
    expect($second->job_instances_id)->toBe($first->job_instances_id);
});
