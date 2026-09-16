<?php

use Illuminate\Support\Facades\Storage;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\DamConfiguration;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\AssetAutoTaggingService;
use Webkul\MagicAI\Models\MagicAIPlatform;

beforeEach(function () {
    Storage::fake(Directory::getAssetDisk());
    config(['dam.ai_tagging.enabled' => true, 'dam.ai_tagging.platform_id' => null, 'dam.ai_tagging.max_tags' => 8]);
});

function makeTaggableAsset(string $fileType = 'image'): Asset
{
    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/tagging-'.uniqid().'.png';

    Storage::disk($disk)->put($path, 'binary-data');

    return Asset::factory()->create([
        'file_type' => $fileType,
        'path'      => $path,
        'mime_type' => 'image/png',
        'extension' => 'png',
    ]);
}

function makeVisionPlatform(bool $active = true): MagicAIPlatform
{
    return MagicAIPlatform::create([
        'label'      => 'Test OpenAI',
        'provider'   => 'openai',
        'api_url'    => 'https://api.openai.com/v1',
        'api_key'    => 'test-key',
        'models'     => 'gpt-4o',
        'extras'     => [],
        'is_default' => true,
        'status'     => $active,
    ]);
}

it('does not call the AI when auto-tagging is disabled', function () {
    config(['dam.ai_tagging.enabled' => false]);
    makeVisionPlatform();

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldNotReceive('configure');
    $client->shouldNotReceive('chat');
    app()->instance(AiApiClient::class, $client);

    $asset = makeTaggableAsset();

    app(AssetAutoTaggingService::class)->tagAsset($asset, Directory::getAssetDisk());

    expect($asset->refresh()->tags)->toBeEmpty();
});

it('skips non-image assets', function () {
    makeVisionPlatform();

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldNotReceive('chat');
    app()->instance(AiApiClient::class, $client);

    $asset = makeTaggableAsset('video');

    app(AssetAutoTaggingService::class)->tagAsset($asset, Directory::getAssetDisk());

    expect($asset->refresh()->tags)->toBeEmpty();
});

it('skips silently when no vision-capable platform is configured', function () {
    $client = Mockery::mock(AiApiClient::class);
    $client->shouldNotReceive('chat');
    app()->instance(AiApiClient::class, $client);

    $asset = makeTaggableAsset();

    app(AssetAutoTaggingService::class)->tagAsset($asset, Directory::getAssetDisk());

    expect($asset->refresh()->tags)->toBeEmpty();
});

it('attaches AI-suggested tags, capped and deduped', function () {
    config(['dam.ai_tagging.max_tags' => 3]);
    makeVisionPlatform();

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->andReturnSelf();
    $client->shouldReceive('chat')->once()->andReturn([
        'content' => json_encode(['tags' => ['Sunset', 'sunset', 'Beach', 'Ocean', 'Sky']]),
    ]);
    app()->instance(AiApiClient::class, $client);

    $asset = makeTaggableAsset();

    app(AssetAutoTaggingService::class)->tagAsset($asset, Directory::getAssetDisk());

    $names = $asset->refresh()->tags->pluck('name')->map(fn ($n) => mb_strtolower($n))->sort()->values()->all();

    expect($names)->toHaveCount(3);
});

it('leaves the asset untagged and does not throw when the AI call fails', function () {
    makeVisionPlatform();

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->andReturnSelf();
    $client->shouldReceive('chat')->once()->andThrow(new RuntimeException('provider timeout'));
    app()->instance(AiApiClient::class, $client);

    $asset = makeTaggableAsset();

    app(AssetAutoTaggingService::class)->tagAsset($asset, Directory::getAssetDisk());

    expect($asset->refresh()->tags)->toBeEmpty();
});

it('reads the enabled flag and platform id from the DB, not just config(), so a queue worker sees them too', function () {
    config(['dam.ai_tagging.enabled' => false, 'dam.ai_tagging.platform_id' => null]);
    $platform = makeVisionPlatform();

    DamConfiguration::create(['key' => 'DAM_AI_TAGGING_ENABLED', 'value' => '1']);
    DamConfiguration::create(['key' => 'DAM_AI_TAGGING_PLATFORM_ID', 'value' => (string) $platform->id]);

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->andReturnSelf();
    $client->shouldReceive('chat')->once()->andReturn(['content' => json_encode(['tags' => ['lake']])]);
    app()->instance(AiApiClient::class, $client);

    $asset = makeTaggableAsset();

    app(AssetAutoTaggingService::class)->tagAsset($asset, Directory::getAssetDisk());

    expect($asset->refresh()->tags->pluck('name')->all())->toBe(['lake']);
});

it('caps tags at a DB-saved max tags value even when config() says a higher default', function () {
    config(['dam.ai_tagging.max_tags' => 8]);
    $platform = makeVisionPlatform();

    DamConfiguration::create(['key' => 'DAM_AI_TAGGING_MAX_TAGS', 'value' => '2']);

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->andReturnSelf();
    $client->shouldReceive('chat')->once()->andReturn([
        'content' => json_encode(['tags' => ['sunset', 'beach', 'ocean', 'sky', 'sand']]),
    ]);
    app()->instance(AiApiClient::class, $client);

    $asset = makeTaggableAsset();

    app(AssetAutoTaggingService::class)->tagAsset($asset, Directory::getAssetDisk());

    expect($asset->refresh()->tags)->toHaveCount(2);
});

it('clamps a DB-saved max tags value above 20, in case validation was bypassed', function () {
    makeVisionPlatform();

    DamConfiguration::create(['key' => 'DAM_AI_TAGGING_MAX_TAGS', 'value' => '999']);

    $tags = collect(range(1, 25))->map(fn ($n) => "tag{$n}")->all();

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->andReturnSelf();
    $client->shouldReceive('chat')->once()->andReturn(['content' => json_encode(['tags' => $tags])]);
    app()->instance(AiApiClient::class, $client);

    $asset = makeTaggableAsset();

    app(AssetAutoTaggingService::class)->tagAsset($asset, Directory::getAssetDisk());

    expect($asset->refresh()->tags)->toHaveCount(20);
});

it('stays disabled when the DB row is off even if config() says enabled', function () {
    config(['dam.ai_tagging.enabled' => true]);
    makeVisionPlatform();

    DamConfiguration::create(['key' => 'DAM_AI_TAGGING_ENABLED', 'value' => '0']);

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldNotReceive('chat');
    app()->instance(AiApiClient::class, $client);

    $asset = makeTaggableAsset();

    app(AssetAutoTaggingService::class)->tagAsset($asset, Directory::getAssetDisk());

    expect($asset->refresh()->tags)->toBeEmpty();
});

it('leaves the asset untagged when the AI response is malformed JSON', function () {
    makeVisionPlatform();

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->andReturnSelf();
    $client->shouldReceive('chat')->once()->andReturn(['content' => 'not json at all']);
    app()->instance(AiApiClient::class, $client);

    $asset = makeTaggableAsset();

    app(AssetAutoTaggingService::class)->tagAsset($asset, Directory::getAssetDisk());

    expect($asset->refresh()->tags)->toBeEmpty();
});

it('skips an image larger than the configured size limit without reading it into memory', function () {
    config(['dam.ai_tagging.max_file_size' => 8]);
    makeVisionPlatform();

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldNotReceive('chat');
    app()->instance(AiApiClient::class, $client);

    $asset = makeTaggableAsset();

    $result = app(AssetAutoTaggingService::class)->tagAsset($asset, Directory::getAssetDisk());

    expect($result)->toBeTrue();
    expect($asset->refresh()->tags)->toBeEmpty();
});

it('applies no size limit when the limit is zero', function () {
    config(['dam.ai_tagging.max_file_size' => 0]);
    makeVisionPlatform();

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->andReturnSelf();
    $client->shouldReceive('chat')->once()->andReturn(['content' => json_encode(['tags' => ['forest']])]);
    app()->instance(AiApiClient::class, $client);

    $asset = makeTaggableAsset();

    app(AssetAutoTaggingService::class)->tagAsset($asset, Directory::getAssetDisk());

    expect($asset->refresh()->tags->pluck('name')->all())->toBe(['forest']);
});
