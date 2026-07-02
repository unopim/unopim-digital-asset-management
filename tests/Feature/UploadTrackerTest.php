<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\DAM\Jobs\ProcessAssetUpload;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\UploadBatch;
use Webkul\DAM\Models\UploadTracker;

beforeEach(function () {
    $this->loginAsAdmin();
    Storage::fake(Directory::getAssetDisk());
});

/**
 * Persist an asset row + its file on the faked asset disk so the background job
 * has something real to finalise.
 */
function makeStoredAsset(string $name = 'floral.png'): Asset
{
    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/'.Str::random(6).'-'.$name;

    Storage::disk($disk)->put($path, (string) UploadedFile::fake()->image($name, 40, 40)->get());

    return Asset::factory()->create([
        'file_name' => $name,
        'file_type' => 'image',
        'path'      => $path,
        'mime_type' => 'image/png',
        'extension' => 'png',
        'meta_data' => null,
    ]);
}

// ── startSession ──────────────────────────────────────────────────────────────

it('starts an upload session tracker', function () {
    $directory = Directory::factory()->create(['name' => 'New']);
    $uuid = (string) Str::uuid();

    $this->postJson(route('admin.dam.assets.upload.tracker'), [
        'session_uuid' => $uuid,
        'directory_id' => $directory->id,
        'total'        => 3,
    ])
        ->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('tracker.state', UploadTracker::STATE_PROCESSING)
        ->assertJsonPath('tracker.total_files', 3);

    $this->assertDatabaseHas($this->getFullTableName(UploadTracker::class), [
        'uuid'        => $uuid,
        'total_files' => 3,
        'state'       => UploadTracker::STATE_PROCESSING,
    ]);
});

it('validates the start session request', function () {
    $this->postJson(route('admin.dam.assets.upload.tracker'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['session_uuid', 'directory_id']);
});

// ── upload wiring ─────────────────────────────────────────────────────────────

it('attaches an upload to a session and finalises it in the background', function () {
    $disk = Directory::getAssetDisk();
    Storage::disk($disk)->makeDirectory('assets/New');
    $directory = Directory::factory()->create(['name' => 'New']);
    $uuid = (string) Str::uuid();

    $this->postJson(route('admin.dam.assets.upload.tracker'), [
        'session_uuid' => $uuid,
        'directory_id' => $directory->id,
        'total'        => 1,
    ])->assertStatus(201);

    $file = UploadedFile::fake()->image('graph-'.uniqid().'.png', 300, 300)->size(20);

    $this->postJson(route('admin.dam.assets.upload'), [
        'files'        => [$file],
        'directory_id' => $directory->id,
        'session_uuid' => $uuid,
    ])->assertStatus(201)->assertJsonPath('success', true);

    // A batch is created for the asset and, under the sync queue, processed.
    $tracker = UploadTracker::where('uuid', $uuid)->first();
    expect($tracker->batches()->count())->toBe(1);
    expect($tracker->batches()->first()->state)->toBe(UploadBatch::STATE_PROCESSED);
    expect($tracker->fresh()->processed_files)->toBe(1);
});

it('uploads without a session create no tracker batches', function () {
    Queue::fake();

    $disk = Directory::getAssetDisk();
    Storage::disk($disk)->makeDirectory('assets/New');
    $directory = Directory::factory()->create(['name' => 'New']);

    $file = UploadedFile::fake()->image('plain-'.uniqid().'.png', 200, 200)->size(15);

    $response = $this->postJson(route('admin.dam.assets.upload'), [
        'files'        => [$file],
        'directory_id' => $directory->id,
    ])->assertStatus(201);

    // Scope the assertion to the freshly-created asset — the shared dev DB may
    // already hold batches from other (committed) upload sessions.
    $assetId = $response->json('files.0.id');
    expect(UploadBatch::where('asset_id', $assetId)->exists())->toBeFalse();
    Queue::assertPushed(ProcessAssetUpload::class);
});

// ── ProcessAssetUpload job ────────────────────────────────────────────────────

it('finalises the asset and completes the tracker when all batches settle', function () {
    $asset = makeStoredAsset();
    $tracker = UploadTracker::create([
        'uuid'        => (string) Str::uuid(),
        'user_id'     => auth()->id(),
        'state'       => UploadTracker::STATE_PROCESSING,
        'total_files' => 1,
    ]);
    $batch = UploadBatch::create([
        'upload_tracker_id' => $tracker->id,
        'asset_id'          => $asset->id,
        'state'             => UploadBatch::STATE_PENDING,
    ]);

    ProcessAssetUpload::dispatch($asset->id, $batch->id);

    expect($batch->fresh()->state)->toBe(UploadBatch::STATE_PROCESSED);
    expect($tracker->fresh()->processed_files)->toBe(1);
    expect($tracker->fresh()->state)->toBe(UploadTracker::STATE_COMPLETED);
});

it('a paused tracker makes the job abort and leaves the batch pending', function () {
    $asset = makeStoredAsset();
    $tracker = UploadTracker::create([
        'uuid'        => (string) Str::uuid(),
        'state'       => UploadTracker::STATE_PAUSED,
        'total_files' => 1,
    ]);
    $batch = UploadBatch::create([
        'upload_tracker_id' => $tracker->id,
        'asset_id'          => $asset->id,
        'state'             => UploadBatch::STATE_PENDING,
    ]);

    ProcessAssetUpload::dispatch($asset->id, $batch->id);

    expect($batch->fresh()->state)->toBe(UploadBatch::STATE_PENDING);
    expect($tracker->fresh()->processed_files)->toBe(0);
});

// ── pause / resume / cancel / retry endpoints ─────────────────────────────────

it('pauses and resumes a session', function () {
    $tracker = UploadTracker::create([
        'uuid'    => (string) Str::uuid(),
        'user_id' => auth()->id(),
        'state'   => UploadTracker::STATE_PROCESSING,
    ]);

    $this->postJson(route('admin.dam.assets.upload.pause', $tracker->uuid))
        ->assertOk()
        ->assertJsonPath('tracker.state', UploadTracker::STATE_PAUSED);

    expect($tracker->fresh()->state)->toBe(UploadTracker::STATE_PAUSED);

    $this->postJson(route('admin.dam.assets.upload.resume', $tracker->uuid))
        ->assertOk()
        ->assertJsonPath('tracker.state', UploadTracker::STATE_PROCESSING);

    expect($tracker->fresh()->state)->toBe(UploadTracker::STATE_PROCESSING);
});

it('cancels a session and abandons pending batches', function () {
    $tracker = UploadTracker::create([
        'uuid'    => (string) Str::uuid(),
        'user_id' => auth()->id(),
        'state'   => UploadTracker::STATE_PROCESSING,
    ]);
    $batch = UploadBatch::create([
        'upload_tracker_id' => $tracker->id,
        'asset_id'          => makeStoredAsset()->id,
        'state'             => UploadBatch::STATE_PENDING,
    ]);

    $this->postJson(route('admin.dam.assets.upload.cancel', $tracker->uuid))
        ->assertOk()
        ->assertJsonPath('tracker.state', UploadTracker::STATE_CANCELLED);

    expect($tracker->fresh()->state)->toBe(UploadTracker::STATE_CANCELLED);
    expect($batch->fresh()->state)->toBe(UploadBatch::STATE_CANCELLED);
});

it('retries only the failed batches', function () {
    $asset = makeStoredAsset();
    $tracker = UploadTracker::create([
        'uuid'         => (string) Str::uuid(),
        'user_id'      => auth()->id(),
        'state'        => UploadTracker::STATE_PROCESSING,
        'total_files'  => 1,
        'failed_files' => 1,
    ]);
    $batch = UploadBatch::create([
        'upload_tracker_id' => $tracker->id,
        'asset_id'          => $asset->id,
        'state'             => UploadBatch::STATE_FAILED,
        'error'             => 'boom',
    ]);

    $this->postJson(route('admin.dam.assets.upload.retry', $tracker->uuid))
        ->assertOk()
        ->assertJsonPath('retried', 1);

    // Under the sync queue the re-dispatched job runs immediately.
    expect($batch->fresh()->state)->toBe(UploadBatch::STATE_PROCESSED);
    expect($tracker->fresh()->failed_files)->toBe(0);
    expect($tracker->fresh()->processed_files)->toBe(1);
});

it('reports stats and 404s for an unknown session', function () {
    $tracker = UploadTracker::create([
        'uuid'    => (string) Str::uuid(),
        'user_id' => auth()->id(),
        'state'   => UploadTracker::STATE_PROCESSING,
    ]);

    $this->getJson(route('admin.dam.assets.upload.stats', $tracker->uuid))
        ->assertOk()
        ->assertJsonPath('tracker.uuid', $tracker->uuid);

    $this->getJson(route('admin.dam.assets.upload.stats', (string) Str::uuid()))
        ->assertStatus(404);
});
