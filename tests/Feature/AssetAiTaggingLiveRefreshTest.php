<?php

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\DAM\Jobs\ProcessAssetUpload;
use Webkul\DAM\Jobs\TagAssetWithAi;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\AiTaggingJobTrackerService;
use Webkul\DAM\Services\AssetAutoTaggingService;
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
