<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\DAM\Jobs\ProcessAssetUpload;
use Webkul\DAM\Jobs\TagAssetWithAi;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\AiTaggingJobTrackerService;
use Webkul\DAM\Services\AssetAutoTaggingService;
use Webkul\DAM\Services\MetadataExtractionService;
use Webkul\MagicAI\Models\MagicAIPlatform;

beforeEach(function () {
    $this->loginAsAdmin();
    Storage::fake(Directory::getAssetDisk());
    config(['dam.ai_tagging.enabled' => true, 'dam.ai_tagging.platform_id' => null, 'dam.ai_tagging.max_tags' => 8]);
});

function liveRefreshImageAsset(): Asset
{
    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/live-'.uniqid().'.png';
    Storage::disk($disk)->put($path, 'binary-data');

    return Asset::factory()->create([
        'file_type' => 'image',
        'path'      => $path,
        'mime_type' => 'image/png',
        'extension' => 'png',
    ]);
}

it('flags the edit page as awaiting AI tags while a tagging job is queued', function () {
    Queue::fake();

    $asset = liveRefreshImageAsset();

    TagAssetWithAi::dispatch($asset->id, Directory::getAssetDisk());

    $this->get(route('admin.dam.assets.edit', $asset->id))
        ->assertOk()
        ->assertViewHas('aiTaggingPending', true);

    $this->getJson(route('admin.dam.assets.show', $asset->id))
        ->assertOk()
        ->assertJsonPath('aiTaggingPending', true);
});

it('flags the asset as awaiting AI tags while its upload finalisation is still queued', function () {
    Queue::fake();

    $asset = liveRefreshImageAsset();

    ProcessAssetUpload::dispatch($asset->id, autoTagEligible: true);

    $this->getJson(route('admin.dam.assets.show', $asset->id))
        ->assertJsonPath('aiTaggingPending', true);
});

it('returns the AI generated tags and clears the pending flag once tagging finishes', function () {
    $asset = liveRefreshImageAsset();

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
    $client->shouldReceive('chat')->once()->andReturn(['content' => json_encode(['tags' => ['desert-dune']])]);
    app()->instance(AiApiClient::class, $client);

    (new TagAssetWithAi($asset->id, Directory::getAssetDisk()))
        ->handle(app(AssetAutoTaggingService::class), app(AiTaggingJobTrackerService::class));

    $this->getJson(route('admin.dam.assets.show', $asset->id))
        ->assertOk()
        ->assertJsonPath('aiTaggingPending', false)
        ->assertJsonPath('tags.0.name', 'desert-dune');
});

it('clears the pending flag when the tagging job fails permanently', function () {
    $asset = liveRefreshImageAsset();

    (new TagAssetWithAi($asset->id, Directory::getAssetDisk()))->failed(new RuntimeException('boom'));

    $this->getJson(route('admin.dam.assets.show', $asset->id))
        ->assertJsonPath('aiTaggingPending', false);
});

it('never flags an asset as awaiting AI tags when tagging is disabled', function () {
    Queue::fake();
    config(['dam.ai_tagging.enabled' => false]);

    $asset = liveRefreshImageAsset();

    TagAssetWithAi::dispatch($asset->id, Directory::getAssetDisk());

    $this->get(route('admin.dam.assets.edit', $asset->id))
        ->assertViewHas('aiTaggingPending', fn ($pending) => $pending === false);
});

it('binds the tag options reactively so newly created AI tags can render', function () {
    $asset = liveRefreshImageAsset();

    $this->get(route('admin.dam.assets.edit', $asset->id))
        ->assertOk()
        ->assertSee(':options="tagOptions"', false);
});

function aiTaggingPendingFlag(int $assetId): bool
{
    return (int) Cache::get("dam.ai_tagging.pending.{$assetId}", 0) > 0;
}

function runAssetFinalisation(ProcessAssetUpload $job): void
{
    $job->handle(
        app(MetadataExtractionService::class),
        app(AssetAutoTaggingService::class),
        app(AiTaggingJobTrackerService::class),
    );
}

it('clears the pending flag when the finalised asset no longer exists', function () {
    $job = new ProcessAssetUpload(987654, autoTagEligible: true);

    expect(aiTaggingPendingFlag(987654))->toBeTrue();

    runAssetFinalisation($job);

    expect(aiTaggingPendingFlag(987654))->toBeFalse();
});

it('clears the pending flag when finalisation queues no tagging job', function () {
    Queue::fake();

    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/finalise-'.uniqid().'.png';
    Storage::disk($disk)->put($path, UploadedFile::fake()->image('finalise.png', 10, 10)->getContent());

    $asset = Asset::factory()->create([
        'file_type' => 'image',
        'path'      => $path,
        'mime_type' => 'image/png',
        'extension' => 'png',
    ]);

    $job = new ProcessAssetUpload($asset->id, autoTagEligible: true);

    config(['dam.ai_tagging.enabled' => false]);

    runAssetFinalisation($job);

    expect(aiTaggingPendingFlag($asset->id))->toBeFalse();

    Queue::assertNotPushed(TagAssetWithAi::class);
});

it('clears the pending flag when upload finalisation fails permanently', function () {
    $asset = liveRefreshImageAsset();

    $job = new ProcessAssetUpload($asset->id, autoTagEligible: true);

    expect(aiTaggingPendingFlag($asset->id))->toBeTrue();

    $job->failed(new RuntimeException('worker killed'));

    expect(aiTaggingPendingFlag($asset->id))->toBeFalse();
});

it('leaves a pending flag owned by another tagging job untouched', function () {
    $asset = liveRefreshImageAsset();

    app(AssetAutoTaggingService::class)->markPending($asset->id);

    (new ProcessAssetUpload($asset->id))->failed(new RuntimeException('worker killed'));

    expect(aiTaggingPendingFlag($asset->id))->toBeTrue();
});

it('tells the editor when the AI tags arrive, or that none were added', function () {
    $asset = liveRefreshImageAsset();

    $this->get(route('admin.dam.assets.edit', $asset->id))
        ->assertOk()
        ->assertSee(json_encode(trans('dam::app.admin.dam.tag.mass-action.ai-tag-success')), false)
        ->assertSee(json_encode(trans('dam::app.admin.dam.tag.mass-action.ai-tag-no-new-tags')), false);
});

it('keeps the asset pending until every overlapping tagging job has settled', function () {
    $asset = liveRefreshImageAsset();
    $disk = Directory::getAssetDisk();

    $first = new TagAssetWithAi($asset->id, $disk);
    $second = new TagAssetWithAi($asset->id, $disk);

    $first->failed(new RuntimeException('boom'));
    $first->failed(new RuntimeException('boom'));

    expect(aiTaggingPendingFlag($asset->id))->toBeTrue();

    $second->failed(new RuntimeException('boom'));

    expect(aiTaggingPendingFlag($asset->id))->toBeFalse();
});

it('keeps the asset pending when upload finalisation hands off to the tagging job', function () {
    Queue::fake();

    $asset = Asset::factory()->create([
        'file_type' => 'image',
        'path'      => tap('assets/Root/handoff-'.uniqid().'.png', fn (string $path) => Storage::disk(Directory::getAssetDisk())
            ->put($path, UploadedFile::fake()->image('handoff.png', 10, 10)->getContent())),
        'mime_type' => 'image/png',
        'extension' => 'png',
    ]);

    $upload = new ProcessAssetUpload($asset->id, autoTagEligible: true);
    $manual = new TagAssetWithAi($asset->id, Directory::getAssetDisk());

    runAssetFinalisation($upload);
    $manual->failed(new RuntimeException('boom'));

    Queue::assertPushed(TagAssetWithAi::class);

    expect(aiTaggingPendingFlag($asset->id))->toBeTrue();
});
