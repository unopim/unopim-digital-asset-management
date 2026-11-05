<?php

use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\DAM\Jobs\TagAssetWithAi;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\UploadBatch;
use Webkul\DAM\Models\UploadTracker;
use Webkul\DAM\Services\AiTaggingJobTrackerService;
use Webkul\DAM\Services\AssetAutoTaggingService;
use Webkul\DataTransfer\Helpers\AbstractJob;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\DataTransfer\Services\JobLogger;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\User\Models\Admin;

beforeEach(function () {
    Storage::fake(Directory::getAssetDisk());
    config(['dam.ai_tagging.enabled' => true, 'dam.ai_tagging.platform_id' => null, 'dam.ai_tagging.max_tags' => 8]);
});

it('is queueable', function () {
    Queue::fake();

    TagAssetWithAi::dispatch(1, Directory::getAssetDisk());

    Queue::assertPushed(TagAssetWithAi::class);
});

it('rate limits itself so a burst of tagging jobs cannot exceed the AI platform quota', function () {
    $middleware = (new TagAssetWithAi(1, Directory::getAssetDisk()))->middleware();

    expect($middleware)->toHaveCount(1);
    expect($middleware[0])->toBeInstanceOf(RateLimited::class);
});

it('tags the asset when handled', function () {
    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/job-'.uniqid().'.png';
    Storage::disk($disk)->put($path, 'binary-data');

    $asset = Asset::factory()->create([
        'file_type' => 'image',
        'path'      => $path,
        'mime_type' => 'image/png',
        'extension' => 'png',
    ]);

    MagicAIPlatform::create([
        'label'      => 'Test OpenAI',
        'provider'   => 'openai',
        'api_url'    => 'https://api.openai.com/v1',
        'api_key'    => 'test-key',
        'models'     => 'gpt-4o',
        'extras'     => [],
        'is_default' => true,
        'status'     => true,
    ]);

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->andReturnSelf();
    $client->shouldReceive('chat')->once()->andReturn(['content' => json_encode(['tags' => ['dune']])]);
    app()->instance(AiApiClient::class, $client);

    (new TagAssetWithAi($asset->id, $disk))->handle(app(AssetAutoTaggingService::class), app(AiTaggingJobTrackerService::class));

    expect($asset->refresh()->tags->pluck('name')->all())->toBe(['dune']);
});

it('does nothing when the asset no longer exists', function () {
    expect(fn () => (new TagAssetWithAi(999999, Directory::getAssetDisk()))
        ->handle(app(AssetAutoTaggingService::class), app(AiTaggingJobTrackerService::class)))
        ->not->toThrow(Throwable::class);
});

it('settles its own linked batch as processed on success, without failing the tracker', function () {
    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/job-'.uniqid().'.png';
    Storage::disk($disk)->put($path, 'binary-data');

    $asset = Asset::factory()->create([
        'file_type' => 'image',
        'path'      => $path,
        'mime_type' => 'image/png',
        'extension' => 'png',
    ]);

    MagicAIPlatform::create([
        'label'   => 'Test OpenAI', 'provider' => 'openai', 'api_url' => 'https://api.openai.com/v1',
        'api_key' => 'test-key', 'models' => 'gpt-4o', 'extras' => [], 'is_default' => true, 'status' => true,
    ]);

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->andReturnSelf();
    $client->shouldReceive('chat')->once()->andReturn(['content' => json_encode(['tags' => ['dune']])]);
    app()->instance(AiApiClient::class, $client);

    $tracker = UploadTracker::create([
        'uuid'  => (string) Str::uuid(), 'directory_id' => Directory::factory()->create()->id,
        'state' => UploadTracker::STATE_PROCESSING, 'total_files' => 1, 'started_at' => now(),
    ]);
    $batch = UploadBatch::create([
        'upload_tracker_id' => $tracker->id, 'asset_id' => $asset->id, 'state' => UploadBatch::STATE_PENDING,
    ]);

    (new TagAssetWithAi($asset->id, $disk, $batch->id))->handle(app(AssetAutoTaggingService::class), app(AiTaggingJobTrackerService::class));

    expect($batch->refresh()->state)->toBe(UploadBatch::STATE_PROCESSED);
    expect($tracker->refresh()->processed_files)->toBe(1);
    expect($tracker->fresh()->state)->toBe(UploadTracker::STATE_COMPLETED);
});

it('settles its own linked batch as failed on a genuine AI error, without touching the tracker success count', function () {
    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/job-'.uniqid().'.png';
    Storage::disk($disk)->put($path, 'binary-data');

    $asset = Asset::factory()->create([
        'file_type' => 'image', 'path' => $path, 'mime_type' => 'image/png', 'extension' => 'png',
    ]);

    MagicAIPlatform::create([
        'label'   => 'Test OpenAI', 'provider' => 'openai', 'api_url' => 'https://api.openai.com/v1',
        'api_key' => 'test-key', 'models' => 'gpt-4o', 'extras' => [], 'is_default' => true, 'status' => true,
    ]);

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->andReturnSelf();
    $client->shouldReceive('chat')->once()->andThrow(new RuntimeException('provider down'));
    app()->instance(AiApiClient::class, $client);

    $tracker = UploadTracker::create([
        'uuid'  => (string) Str::uuid(), 'directory_id' => Directory::factory()->create()->id,
        'state' => UploadTracker::STATE_PROCESSING, 'total_files' => 1, 'started_at' => now(),
    ]);
    $batch = UploadBatch::create([
        'upload_tracker_id' => $tracker->id, 'asset_id' => $asset->id, 'state' => UploadBatch::STATE_PENDING,
    ]);

    (new TagAssetWithAi($asset->id, $disk, $batch->id))->handle(app(AssetAutoTaggingService::class), app(AiTaggingJobTrackerService::class));

    expect($batch->refresh()->state)->toBe(UploadBatch::STATE_FAILED);
    expect($tracker->refresh()->failed_files)->toBe(1);
    expect($tracker->processed_files)->toBe(0);
});

it('writes a processed job_track_batches row for a session-tracked run, so the live view shows real progress', function () {
    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/job-'.uniqid().'.png';
    Storage::disk($disk)->put($path, 'binary-data');

    $asset = Asset::factory()->create([
        'file_type' => 'image', 'path' => $path, 'mime_type' => 'image/png', 'extension' => 'png',
    ]);

    MagicAIPlatform::create([
        'label'   => 'Test OpenAI', 'provider' => 'openai', 'api_url' => 'https://api.openai.com/v1',
        'api_key' => 'test-key', 'models' => 'gpt-4o', 'extras' => [], 'is_default' => true, 'status' => true,
    ]);

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->andReturnSelf();
    $client->shouldReceive('chat')->once()->andReturn(['content' => json_encode(['tags' => ['dune']])]);
    app()->instance(AiApiClient::class, $client);

    $tracker = UploadTracker::create([
        'uuid'  => (string) Str::uuid(), 'directory_id' => Directory::factory()->create()->id,
        'state' => UploadTracker::STATE_PROCESSING, 'total_files' => 1, 'started_at' => now(),
    ]);
    $batch = UploadBatch::create([
        'upload_tracker_id' => $tracker->id, 'asset_id' => $asset->id, 'state' => UploadBatch::STATE_PENDING,
    ]);

    $jobTrackerService = app(AiTaggingJobTrackerService::class);
    $jobTrack = $jobTrackerService->ensureSessionJob($tracker);

    (new TagAssetWithAi($asset->id, $disk, $batch->id))->handle(app(AssetAutoTaggingService::class), $jobTrackerService);

    $trackBatch = JobTrackBatch::where('job_track_id', $jobTrack->id)->first();
    expect($trackBatch)->not->toBeNull();
    expect($trackBatch->state)->toBe(AbstractJob::STATE_PROCESSED);
    expect($trackBatch->summary)->toEqual(['created' => 0, 'updated' => 1, 'deleted' => 0]);
});

it('logs a session-job failure to the downloadable job log, since a session never flips to the failed state', function () {
    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/job-'.uniqid().'.png';
    Storage::disk($disk)->put($path, 'binary-data');

    $asset = Asset::factory()->create([
        'file_type' => 'image', 'path' => $path, 'mime_type' => 'image/png', 'extension' => 'png',
    ]);

    MagicAIPlatform::create([
        'label'   => 'Test OpenAI', 'provider' => 'openai', 'api_url' => 'https://api.openai.com/v1',
        'api_key' => 'test-key', 'models' => 'gpt-4o', 'extras' => [], 'is_default' => true, 'status' => true,
    ]);

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->andReturnSelf();
    $client->shouldReceive('chat')->once()->andThrow(new RuntimeException('AI API error (429): Unknown API error'));
    app()->instance(AiApiClient::class, $client);

    $tracker = UploadTracker::create([
        'uuid'  => (string) Str::uuid(), 'directory_id' => Directory::factory()->create()->id,
        'state' => UploadTracker::STATE_PROCESSING, 'total_files' => 1, 'started_at' => now(),
    ]);
    $batch = UploadBatch::create([
        'upload_tracker_id' => $tracker->id, 'asset_id' => $asset->id, 'state' => UploadBatch::STATE_PENDING,
    ]);

    $jobTrackerService = app(AiTaggingJobTrackerService::class);
    $jobTrack = $jobTrackerService->ensureSessionJob($tracker);

    (new TagAssetWithAi($asset->id, $disk, $batch->id))->handle(app(AssetAutoTaggingService::class), $jobTrackerService);

    $logPath = storage_path(JobLogger::getJobLogPath($jobTrack->id));
    expect(file_exists($logPath))->toBeTrue();
    expect(file_get_contents($logPath))->toContain("Asset {$asset->id}")
        ->toContain('AI API error (429): Unknown API error');
});

it('gives a batchless (e.g. API) run its own job_track, failed with the real error message on failure', function () {
    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/job-'.uniqid().'.png';
    Storage::disk($disk)->put($path, 'binary-data');

    $asset = Asset::factory()->create([
        'file_type' => 'image', 'path' => $path, 'mime_type' => 'image/png', 'extension' => 'png',
    ]);

    MagicAIPlatform::create([
        'label'   => 'Test OpenAI', 'provider' => 'openai', 'api_url' => 'https://api.openai.com/v1',
        'api_key' => 'test-key', 'models' => 'gpt-4o', 'extras' => [], 'is_default' => true, 'status' => true,
    ]);

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->andReturnSelf();
    $client->shouldReceive('chat')->once()->andThrow(new RuntimeException('AI API error (503): Unknown API error'));
    app()->instance(AiApiClient::class, $client);

    $admin = Admin::factory()->create();

    (new TagAssetWithAi($asset->id, $disk, batchId: null, userId: $admin->id))
        ->handle(app(AssetAutoTaggingService::class), app(AiTaggingJobTrackerService::class));

    $jobTrack = JobTrack::whereHas(
        'jobInstance',
        fn ($q) => $q->where('code', 'dam_ai_tagging')
    )->latest('id')->first();

    expect($jobTrack)->not->toBeNull();
    expect($jobTrack->state)->toBe(AbstractJob::STATE_FAILED);
    expect($jobTrack->user_id)->toBe($admin->id);
    expect($jobTrack->errors)->toBe(['AI API error (503): Unknown API error']);
});

it('clamps an out-of-range configured rate limit, so tagging is never blocked outright', function () {
    config(['dam.ai_tagging.rate_limit_per_minute' => 0]);

    expect(call_user_func(RateLimiter::limiter('dam-ai-tagging'))->maxAttempts)->toBe(1);

    config(['dam.ai_tagging.rate_limit_per_minute' => 5000]);

    expect(call_user_func(RateLimiter::limiter('dam-ai-tagging'))->maxAttempts)->toBe(120);
});

it('settles its linked upload and job-track batches as failed when the job fails permanently', function () {
    $asset = Asset::factory()->create(['file_type' => 'image']);

    $tracker = UploadTracker::create([
        'uuid'  => (string) Str::uuid(), 'directory_id' => Directory::factory()->create()->id,
        'state' => UploadTracker::STATE_PROCESSING, 'total_files' => 1, 'started_at' => now(),
    ]);
    $batch = UploadBatch::create([
        'upload_tracker_id' => $tracker->id, 'asset_id' => $asset->id, 'state' => UploadBatch::STATE_PENDING,
    ]);

    $jobTrackerService = app(AiTaggingJobTrackerService::class);
    $jobTrack = $jobTrackerService->ensureSessionJob($tracker);
    $trackBatch = $jobTrackerService->startBatch($jobTrack);

    $job = new TagAssetWithAi($asset->id, Directory::getAssetDisk(), $batch->id, trackBatchId: $trackBatch->id);

    $job->failed(new RuntimeException('max attempts exceeded'));
    $job->failed(new RuntimeException('max attempts exceeded'));

    expect($batch->refresh()->state)->toBe(UploadBatch::STATE_FAILED)
        ->and($tracker->refresh()->failed_files)->toBe(1)
        ->and($tracker->state)->toBe(UploadTracker::STATE_COMPLETED)
        ->and($trackBatch->refresh()->state)->toBe(AbstractJob::STATE_PROCESSED)
        ->and($trackBatch->summary)->toEqual(['created' => 0, 'updated' => 0, 'deleted' => 0])
        ->and($jobTrack->refresh()->invalid_rows_count)->toBe(1);
});

it('does not count a permanent failure again when handle already settled its batch', function () {
    $asset = Asset::factory()->create(['file_type' => 'image']);

    $tracker = UploadTracker::create([
        'uuid'  => (string) Str::uuid(), 'directory_id' => Directory::factory()->create()->id,
        'state' => UploadTracker::STATE_PROCESSING, 'total_files' => 2, 'processed_files' => 1, 'started_at' => now(),
    ]);
    $batch = UploadBatch::create([
        'upload_tracker_id' => $tracker->id, 'asset_id' => $asset->id, 'state' => UploadBatch::STATE_PROCESSED,
    ]);

    (new TagAssetWithAi($asset->id, Directory::getAssetDisk(), $batch->id))->failed(new RuntimeException('late failure'));

    expect($batch->refresh()->state)->toBe(UploadBatch::STATE_PROCESSED)
        ->and($tracker->refresh()->failed_files)->toBe(0)
        ->and($tracker->processed_files)->toBe(1);
});

function failingTaggingService(): AssetAutoTaggingService
{
    $service = Mockery::mock(AssetAutoTaggingService::class)->makePartial();
    $service->shouldReceive('tagAsset')->andThrow(new RuntimeException('worker timed out'));

    return $service;
}

function standaloneTaggingJobTracks(): Collection
{
    return JobTrack::whereHas('jobInstance', fn ($q) => $q->where('code', 'dam_ai_tagging'))->get();
}

it('fails the job track of a batchless (e.g. API) run when the job fails permanently outside handle', function () {
    $asset = Asset::factory()->create(['file_type' => 'image']);
    $admin = Admin::factory()->create();
    $before = standaloneTaggingJobTracks()->pluck('id');

    $job = new TagAssetWithAi($asset->id, Directory::getAssetDisk(), batchId: null, userId: $admin->id);

    expect(fn () => $job->handle(failingTaggingService(), app(AiTaggingJobTrackerService::class)))
        ->toThrow(RuntimeException::class);

    $job->failed(new RuntimeException('worker timed out'));
    $job->failed(new RuntimeException('worker timed out'));

    $jobTrack = standaloneTaggingJobTracks()->whereNotIn('id', $before)->sole()->refresh();
    $trackBatch = JobTrackBatch::where('job_track_id', $jobTrack->id)->sole();

    expect($jobTrack->state)->toBe(AbstractJob::STATE_FAILED)
        ->and($jobTrack->errors)->toBe(['worker timed out'])
        ->and($jobTrack->invalid_rows_count)->toBe(1)
        ->and($trackBatch->state)->toBe(AbstractJob::STATE_PROCESSED)
        ->and($trackBatch->summary)->toEqual(['created' => 0, 'updated' => 0, 'deleted' => 0]);
});

it('reuses the job track of a batchless run when the job is retried', function () {
    $asset = Asset::factory()->create(['file_type' => 'image']);
    $before = standaloneTaggingJobTracks()->pluck('id');

    $job = new TagAssetWithAi($asset->id, Directory::getAssetDisk());

    foreach (range(1, 2) as $attempt) {
        expect(fn () => $job->handle(failingTaggingService(), app(AiTaggingJobTrackerService::class)))
            ->toThrow(RuntimeException::class);
    }

    $jobTrack = standaloneTaggingJobTracks()->whereNotIn('id', $before)->sole();

    expect(JobTrackBatch::where('job_track_id', $jobTrack->id)->count())->toBe(1);
});

it('leaves a batchless run that already completed untouched when a late failure arrives', function () {
    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/job-'.uniqid().'.png';
    Storage::disk($disk)->put($path, 'binary-data');

    $asset = Asset::factory()->create([
        'file_type' => 'image', 'path' => $path, 'mime_type' => 'image/png', 'extension' => 'png',
    ]);

    MagicAIPlatform::create([
        'label'   => 'Test OpenAI', 'provider' => 'openai', 'api_url' => 'https://api.openai.com/v1',
        'api_key' => 'test-key', 'models' => 'gpt-4o', 'extras' => [], 'is_default' => true, 'status' => true,
    ]);

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->andReturnSelf();
    $client->shouldReceive('chat')->once()->andReturn(['content' => json_encode(['tags' => ['dune']])]);
    app()->instance(AiApiClient::class, $client);

    $before = standaloneTaggingJobTracks()->pluck('id');

    $job = new TagAssetWithAi($asset->id, $disk);
    $job->handle(app(AssetAutoTaggingService::class), app(AiTaggingJobTrackerService::class));
    $job->failed(new RuntimeException('late failure'));

    $jobTrack = standaloneTaggingJobTracks()->whereNotIn('id', $before)->sole()->refresh();

    expect($jobTrack->state)->toBe(AbstractJob::STATE_COMPLETED)
        ->and($jobTrack->processed_rows_count)->toBe(1)
        ->and($jobTrack->invalid_rows_count)->toBe(0);
});
