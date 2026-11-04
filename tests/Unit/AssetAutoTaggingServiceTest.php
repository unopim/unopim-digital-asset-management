<?php

use Illuminate\Support\Facades\Storage;
use Webkul\AiAgent\DTOs\CredentialConfig;
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

function makeVisionPlatform(bool $active = true, string $models = 'gpt-4o'): MagicAIPlatform
{
    return MagicAIPlatform::create([
        'label'      => 'Test OpenAI',
        'provider'   => 'openai',
        'api_url'    => 'https://api.openai.com/v1',
        'api_key'    => 'test-key',
        'models'     => $models,
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

it('sends the default temperature to openai reasoning models that reject any other value', function (string $model) {
    makeVisionPlatform(models: $model);

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->andReturnSelf();
    $client->shouldReceive('chat')->once()->withArgs(fn ($messages, $maxTokens, $temperature) => $temperature === 1.0)->andReturn([
        'content' => json_encode(['tags' => ['forest']]),
    ]);
    app()->instance(AiApiClient::class, $client);

    $asset = makeTaggableAsset();

    expect(app(AssetAutoTaggingService::class)->tagAsset($asset, Directory::getAssetDisk()))->toBeTrue()
        ->and($asset->refresh()->tags->pluck('name')->all())->toBe(['forest']);
})->with(['gpt-6.1-terra', 'gpt-6-luna', 'gpt-5-mini', 'o3']);

it('keeps a low temperature for models that accept one', function () {
    makeVisionPlatform(models: 'gpt-4o');

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->andReturnSelf();
    $client->shouldReceive('chat')->once()->withArgs(fn ($messages, $maxTokens, $temperature) => $temperature === 0.2)->andReturn([
        'content' => json_encode(['tags' => ['forest']]),
    ]);
    app()->instance(AiApiClient::class, $client);

    expect(app(AssetAutoTaggingService::class)->tagAsset(makeTaggableAsset(), Directory::getAssetDisk()))->toBeTrue();
});

it('lists only the configured platform models that support vision', function () {
    $platform = makeVisionPlatform(models: 'gpt-4o,gpt-image-1,dall-e-3,text-embedding-3-small,whisper-1,tts-1,omni-moderation-latest,gpt-4o-transcribe,gpt-3.5-turbo,o3-mini,gpt-5-mini');

    expect(app(AssetAutoTaggingService::class)->taggingModels($platform))->toBe(['gpt-4o', 'gpt-5-mini']);
});

it('recognises vision models across providers', function (string $model) {
    expect(app(AssetAutoTaggingService::class)->taggingModels(makeVisionPlatform(models: $model)))->toBe([$model]);
})->with([
    'gpt-4o-mini', 'chatgpt-4o-latest', 'gpt-4.1-nano', 'gpt-4-turbo', 'gpt-5.1', 'gpt-6.1-terra', 'o1', 'o3', 'o4-mini',
    'claude-3-5-sonnet-latest', 'claude-sonnet-4-5', 'claude-opus-4-1',
    'gemini-2.5-flash', 'gemini-1.5-pro', 'gemma-3-27b-it',
    'grok-2-vision-1212', 'grok-4',
    'llama-3.2-11b-vision-instruct', 'meta-llama/llama-4-scout', 'llava:13b',
    'pixtral-large-latest', 'mistral-medium-latest', 'qwen2.5-vl-72b-instruct', 'openai/gpt-4o',
]);

it('leaves out chat models without vision support', function (string $model) {
    expect(app(AssetAutoTaggingService::class)->taggingModels(makeVisionPlatform(models: $model)))->toBe([]);
})->with(['gpt-3.5-turbo', 'gpt-4', 'o1-mini', 'o3-mini', 'claude-2.1', 'gemini-1.0-pro', 'llama-3.1-8b-instant', 'mistral-large-latest', 'deepseek-chat', 'grok-3-mini']);

it('tags with the model saved in the DAM configuration', function () {
    makeVisionPlatform(models: 'gpt-4o,gpt-4.1-mini');
    DamConfiguration::updateOrCreate(['key' => 'DAM_AI_TAGGING_ENABLED'], ['value' => '1']);
    DamConfiguration::updateOrCreate(['key' => 'DAM_AI_TAGGING_MODEL'], ['value' => 'gpt-4.1-mini']);

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->withArgs(fn (CredentialConfig $config) => $config->model === 'gpt-4.1-mini')->andReturnSelf();
    $client->shouldReceive('chat')->once()->andReturn(['content' => json_encode(['tags' => ['forest']])]);
    app()->instance(AiApiClient::class, $client);

    expect(app(AssetAutoTaggingService::class)->tagAsset(makeTaggableAsset(), Directory::getAssetDisk()))->toBeTrue();
});

it('falls back to the first compatible model when none is saved', function () {
    makeVisionPlatform(models: 'gpt-image-1,gpt-4o');

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->withArgs(fn (CredentialConfig $config) => $config->model === 'gpt-4o')->andReturnSelf();
    $client->shouldReceive('chat')->once()->andReturn(['content' => json_encode(['tags' => ['forest']])]);
    app()->instance(AiApiClient::class, $client);

    expect(app(AssetAutoTaggingService::class)->tagAsset(makeTaggableAsset(), Directory::getAssetDisk()))->toBeTrue();
});

it('falls back to the first compatible model when the saved one is no longer on the platform', function () {
    makeVisionPlatform(models: 'gpt-4o');
    DamConfiguration::updateOrCreate(['key' => 'DAM_AI_TAGGING_ENABLED'], ['value' => '1']);
    DamConfiguration::updateOrCreate(['key' => 'DAM_AI_TAGGING_MODEL'], ['value' => 'gpt-4.1-mini']);

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->once()->withArgs(fn (CredentialConfig $config) => $config->model === 'gpt-4o')->andReturnSelf();
    $client->shouldReceive('chat')->once()->andReturn(['content' => json_encode(['tags' => ['forest']])]);
    app()->instance(AiApiClient::class, $client);

    expect(app(AssetAutoTaggingService::class)->tagAsset(makeTaggableAsset(), Directory::getAssetDisk()))->toBeTrue();
});

it('does not call the AI when the platform has no model that can tag an image', function () {
    makeVisionPlatform(models: 'gpt-image-1,dall-e-3');

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldNotReceive('chat');
    app()->instance(AiApiClient::class, $client);

    $service = app(AssetAutoTaggingService::class);

    expect($service->isAvailable())->toBeFalse()
        ->and($service->tagAsset(makeTaggableAsset(), Directory::getAssetDisk()))->toBeTrue();
});
