<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\DAM\Jobs\TagAssetWithAi;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\Tag;
use Webkul\DAM\Models\UploadTracker;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

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

function apiAiTagAsset(string $fileType = 'image', string $extension = 'png'): Asset
{
    $path = 'assets/Root/api-ai-'.uniqid().'.'.$extension;
    Storage::disk(Directory::getAssetDisk())->put($path, 'binary-data');

    return Asset::factory()->create([
        'file_type' => $fileType,
        'path'      => $path,
        'mime_type' => $fileType === 'image' ? 'image/'.$extension : 'application/'.$extension,
        'extension' => $extension,
    ]);
}

it('queues AI tagging for a single existing image via the api', function () {
    Queue::fake();

    $asset = apiAiTagAsset();

    $this->withHeaders($this->getAuthenticationHeaders())
        ->postJson(route('admin.api.dam.tags.ai_tag'), ['asset_ids' => [$asset->id]])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('queued', 1)
        ->assertJsonPath('skipped', 0)
        ->assertJsonPath('message', trans('dam::app.admin.dam.tag.mass-action.ai-tag-queued', ['count' => 1]));

    Queue::assertPushed(TagAssetWithAi::class, 1);
});

it('queues several existing images in one tracked run and skips non-images via the api', function () {
    Queue::fake();

    $images = collect(range(1, 2))->map(fn () => apiAiTagAsset());
    $pdf = apiAiTagAsset('document', 'pdf');

    $this->withHeaders($this->getAuthenticationHeaders())
        ->postJson(route('admin.api.dam.tags.ai_tag'), ['asset_ids' => [...$images->pluck('id')->all(), $pdf->id]])
        ->assertOk()
        ->assertJsonPath('queued', 2)
        ->assertJsonPath('skipped', 1);

    Queue::assertPushed(TagAssetWithAi::class, 2);

    expect(UploadTracker::latest('id')->first()->total_files)->toBe(2);
});

it('adds AI tags to an existing asset alongside its current tags via the api', function () {
    $asset = apiAiTagAsset();
    $asset->tags()->attach(Tag::create(['name' => 'manual-tag']));

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->andReturnSelf();
    $client->shouldReceive('chat')->andReturn(['content' => json_encode(['tags' => ['sunset']])]);
    app()->instance(AiApiClient::class, $client);

    $this->withHeaders($this->getAuthenticationHeaders())
        ->postJson(route('admin.api.dam.tags.ai_tag'), ['asset_ids' => [$asset->id]])
        ->assertOk();

    expect($asset->refresh()->tags->pluck('name')->sort()->values()->all())->toBe(['manual-tag', 'sunset']);
});

it('refuses to queue anything via the api when AI tagging is unavailable', function () {
    Queue::fake();
    config(['dam.ai_tagging.enabled' => false]);

    $asset = apiAiTagAsset();

    $this->withHeaders($this->getAuthenticationHeaders())
        ->postJson(route('admin.api.dam.tags.ai_tag'), ['asset_ids' => [$asset->id]])
        ->assertStatus(422)
        ->assertJsonPath('message', trans('dam::app.admin.dam.tag.mass-action.ai-tag-unavailable'));

    Queue::assertNotPushed(TagAssetWithAi::class);
});

it('reports via the api when none of the given assets are images', function () {
    Queue::fake();

    $pdf = apiAiTagAsset('document', 'pdf');

    $this->withHeaders($this->getAuthenticationHeaders())
        ->postJson(route('admin.api.dam.tags.ai_tag'), ['asset_ids' => [$pdf->id]])
        ->assertStatus(422)
        ->assertJsonPath('message', trans('dam::app.admin.dam.tag.mass-action.ai-tag-none'));

    Queue::assertNotPushed(TagAssetWithAi::class);
});

it('validates the asset ids sent to the api', function () {
    $headers = $this->getAuthenticationHeaders();

    $this->withHeaders($headers)
        ->postJson(route('admin.api.dam.tags.ai_tag'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('asset_ids');

    $this->withHeaders($headers)
        ->postJson(route('admin.api.dam.tags.ai_tag'), ['asset_ids' => ['abc']])
        ->assertStatus(422)
        ->assertJsonValidationErrors('asset_ids.0');
});

it('forbids an api key without the AI tagging scope', function () {
    Queue::fake();

    $asset = apiAiTagAsset();

    $this->withHeaders($this->getAuthenticationHeaders('custom', ['api.dam.tags.create', 'api.dam.assets.update']))
        ->postJson(route('admin.api.dam.tags.ai_tag'), ['asset_ids' => [$asset->id]])
        ->assertForbidden();

    Queue::assertNotPushed(TagAssetWithAi::class);
});

it('allows an api key granted only the AI tagging scope', function () {
    Queue::fake();

    $asset = apiAiTagAsset();

    $this->withHeaders($this->getAuthenticationHeaders('custom', ['api.dam.tags.ai-tag']))
        ->postJson(route('admin.api.dam.tags.ai_tag'), ['asset_ids' => [$asset->id]])
        ->assertOk()
        ->assertJsonPath('queued', 1);

    Queue::assertPushed(TagAssetWithAi::class, 1);
});

it('skips assets in directories the api user cannot access', function () {
    Queue::fake();

    $denied = Directory::factory()->create();
    $granted = Directory::factory()->create();

    $blocked = apiAiTagAsset();
    $blocked->directories()->attach($denied->id);

    $allowed = apiAiTagAsset();
    $allowed->directories()->attach($granted->id);

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => []]);
    DB::table('dam_directory_role')->insert([
        'directory_id' => $granted->id,
        'role_id'      => $role->id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    $headers = $this->getAuthenticationHeaders();
    Admin::latest('id')->first()->update(['role_id' => $role->id]);
    app(DirectoryPermissionService::class)->flush();

    $this->withHeaders($headers)
        ->postJson(route('admin.api.dam.tags.ai_tag'), ['asset_ids' => [$blocked->id, $allowed->id]])
        ->assertOk()
        ->assertJsonPath('queued', 1)
        ->assertJsonPath('skipped', 1);

    Queue::assertPushed(TagAssetWithAi::class, fn (TagAssetWithAi $job) => (fn () => $this->assetId)->call($job) === $allowed->id);
    Queue::assertPushed(TagAssetWithAi::class, 1);
});
