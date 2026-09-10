<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\DAM\Jobs\ProcessAssetUpload;
use Webkul\DAM\Jobs\TagAssetWithAi;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\UploadBatch;
use Webkul\DAM\Models\UploadTracker;
use Webkul\DAM\Services\MetadataExtractionService;
use Webkul\MagicAI\Models\MagicAIPlatform;

beforeEach(function () {
    Storage::fake(Directory::getAssetDisk());
    config(['dam.ai_tagging.enabled' => true, 'dam.ai_tagging.platform_id' => null, 'dam.ai_tagging.max_tags' => 8]);

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
});

function storedTaggableAsset(string $fileType = 'image', string $extension = 'png'): Asset
{
    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/tagging-'.uniqid().'.'.$extension;
    Storage::disk($disk)->put($path, 'binary-data');

    return Asset::factory()->create([
        'file_type' => $fileType,
        'path'      => $path,
        'mime_type' => $fileType === 'image' ? 'image/png' : 'video/mp4',
        'extension' => $extension,
    ]);
}

function fakeChatClient(array $return): void
{
    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->andReturnSelf();
    $client->shouldReceive('chat')->andReturn($return);
    app()->instance(AiApiClient::class, $client);
}

it('tags an eligible image upload end to end', function () {
    fakeChatClient(['content' => json_encode(['tags' => ['sunset', 'beach']])]);

    $asset = storedTaggableAsset();

    (new ProcessAssetUpload($asset->id, null, autoTagEligible: true))->handle(
        app(MetadataExtractionService::class),
    );

    expect($asset->refresh()->tags->pluck('name')->sort()->values()->all())->toBe(['beach', 'sunset']);
});

it('never tags a non-image upload regardless of the permission flag, and never dispatches a tagging job for it', function () {
    Queue::fake();

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldNotReceive('chat');
    app()->instance(AiApiClient::class, $client);

    $asset = storedTaggableAsset('video', 'mp4');

    (new ProcessAssetUpload($asset->id, null, autoTagEligible: true))->handle(
        app(MetadataExtractionService::class),
    );

    Queue::assertNotPushed(TagAssetWithAi::class);
    expect($asset->refresh()->tags)->toBeEmpty();
});

it('does not call the AI when the feature is disabled', function () {
    config(['dam.ai_tagging.enabled' => false]);

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldNotReceive('chat');
    app()->instance(AiApiClient::class, $client);

    $asset = storedTaggableAsset();

    (new ProcessAssetUpload($asset->id, null, autoTagEligible: true))->handle(
        app(MetadataExtractionService::class),
    );

    expect($asset->refresh()->tags)->toBeEmpty();
});

it('does not call the AI when the uploading admin was not auto-tag eligible', function () {
    $client = Mockery::mock(AiApiClient::class);
    $client->shouldNotReceive('chat');
    app()->instance(AiApiClient::class, $client);

    $asset = storedTaggableAsset();

    (new ProcessAssetUpload($asset->id, null, autoTagEligible: false))->handle(
        app(MetadataExtractionService::class),
    );

    expect($asset->refresh()->tags)->toBeEmpty();
});

it('dispatches tagging as its own job, separate from ProcessAssetUpload', function () {
    Queue::fake();

    $asset = storedTaggableAsset();

    (new ProcessAssetUpload($asset->id, null, autoTagEligible: true))->handle(
        app(MetadataExtractionService::class),
    );

    Queue::assertPushed(TagAssetWithAi::class, function (TagAssetWithAi $job) use ($asset) {
        return (fn () => $this->assetId)->call($job) === $asset->id;
    });
});

it('completes the upload batch even when the AI call fails', function () {
    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->andReturnSelf();
    $client->shouldReceive('chat')->andThrow(new RuntimeException('provider down'));
    app()->instance(AiApiClient::class, $client);

    $tracker = UploadTracker::create([
        'uuid'         => (string) Str::uuid(),
        'directory_id' => Directory::factory()->create()->id,
        'state'        => UploadTracker::STATE_PROCESSING,
        'total_files'  => 1,
        'started_at'   => now(),
    ]);
    $asset = storedTaggableAsset();
    $batch = UploadBatch::create([
        'upload_tracker_id' => $tracker->id,
        'asset_id'          => $asset->id,
        'state'             => UploadBatch::STATE_PENDING,
    ]);

    (new ProcessAssetUpload($asset->id, $batch->id, autoTagEligible: true))->handle(
        app(MetadataExtractionService::class),
    );

    expect($batch->refresh()->state)->toBe(UploadBatch::STATE_PROCESSED);
    expect($asset->refresh()->tags)->toBeEmpty();
});

it('dispatches the finalisation job with autoTagEligible false when the admin lacks tag-create permission', function () {
    Queue::fake();
    $admin = $this->loginWithPermissions('custom', ['dam.asset.upload', 'dam.asset.update']);

    Storage::disk(Directory::getAssetDisk())->makeDirectory('assets/New');
    $directory = Directory::factory()->create(['name' => 'New', 'parent_id' => null]);

    DB::table('dam_directory_role')->insert([
        'directory_id' => $directory->id,
        'role_id'      => $admin->role_id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    $file = UploadedFile::fake()->image('pic.png', 20, 20);

    $this->postJson(route('admin.dam.assets.upload'), [
        'files'        => [$file],
        'directory_id' => $directory->id,
    ])->assertStatus(201);

    Queue::assertPushed(ProcessAssetUpload::class, function (ProcessAssetUpload $job) {
        return (fn () => $this->autoTagEligible)->call($job) === false;
    });
});
