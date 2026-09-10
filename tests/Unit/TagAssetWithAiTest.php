<?php

use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\DAM\Jobs\TagAssetWithAi;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\AssetAutoTaggingService;
use Webkul\MagicAI\Models\MagicAIPlatform;

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

    (new TagAssetWithAi($asset->id, $disk))->handle(app(AssetAutoTaggingService::class));

    expect($asset->refresh()->tags->pluck('name')->all())->toBe(['dune']);
});

it('does nothing when the asset no longer exists', function () {
    expect(fn () => (new TagAssetWithAi(999999, Directory::getAssetDisk()))
        ->handle(app(AssetAutoTaggingService::class)))
        ->not->toThrow(Throwable::class);
});
