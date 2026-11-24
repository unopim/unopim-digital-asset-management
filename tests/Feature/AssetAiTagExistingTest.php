<?php

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\DAM\DataGrids\Asset\AssetDataGrid;
use Webkul\DAM\Jobs\TagAssetWithAi;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\Tag;
use Webkul\DAM\Models\UploadBatch;
use Webkul\DAM\Models\UploadTracker;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\DataTransfer\Helpers\AbstractJob;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\MagicAI\Models\MagicAIPlatform;

beforeEach(function () {
    $this->loginAsAdmin();
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

function existingAsset(string $fileType = 'image', string $extension = 'png'): Asset
{
    $path = 'assets/Root/existing-'.uniqid().'.'.$extension;
    Storage::disk(Directory::getAssetDisk())->put($path, 'binary-data');

    return Asset::factory()->create([
        'file_type' => $fileType,
        'path'      => $path,
        'mime_type' => $fileType === 'image' ? 'image/'.$extension : 'application/'.$extension,
        'extension' => $extension,
    ]);
}

it('queues AI tagging for a single existing image', function () {
    Queue::fake();

    $asset = existingAsset();

    $this->postJson(route('admin.dam.assets.mass_ai_tag'), ['indices' => [$asset->id]])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('queued', 1)
        ->assertJsonPath('skipped', 0)
        ->assertJsonPath('message', trans('dam::app.admin.dam.tag.mass-action.ai-tag-queued', ['count' => 1]));

    Queue::assertPushed(TagAssetWithAi::class, 1);
});

it('queues every selected image in one tracked run and skips non-images', function () {
    Queue::fake();

    $images = collect(range(1, 3))->map(fn () => existingAsset());
    $pdf = existingAsset('document', 'pdf');

    $this->postJson(route('admin.dam.assets.mass_ai_tag'), [
        'indices' => [...$images->pluck('id')->all(), $pdf->id],
    ])
        ->assertOk()
        ->assertJsonPath('queued', 3)
        ->assertJsonPath('skipped', 1)
        ->assertJsonPath('message', trans('dam::app.admin.dam.tag.mass-action.ai-tag-queued-skipped', ['count' => 3, 'skipped' => 1]));

    Queue::assertPushed(TagAssetWithAi::class, 3);

    $tracker = UploadTracker::latest('id')->first();

    expect($tracker->total_files)->toBe(3)
        ->and($tracker->job_track_id)->not->toBeNull()
        ->and(UploadBatch::where('upload_tracker_id', $tracker->id)->pluck('asset_id')->sort()->values()->all())
        ->toBe($images->pluck('id')->sort()->values()->all());
});

it('adds AI tags to existing assets without removing their current tags and completes the run', function () {
    $first = existingAsset();
    $second = existingAsset();
    $manual = Tag::create(['name' => 'manual-tag']);
    $first->tags()->attach($manual);

    $client = Mockery::mock(AiApiClient::class);
    $client->shouldReceive('configure')->andReturnSelf();
    $client->shouldReceive('chat')->andReturn(['content' => json_encode(['tags' => ['sunset']])]);
    app()->instance(AiApiClient::class, $client);

    $this->postJson(route('admin.dam.assets.mass_ai_tag'), ['indices' => [$first->id, $second->id]])
        ->assertOk();

    expect($first->refresh()->tags->pluck('name')->sort()->values()->all())->toBe(['manual-tag', 'sunset'])
        ->and($second->refresh()->tags->pluck('name')->all())->toBe(['sunset']);

    $tracker = UploadTracker::latest('id')->first();

    expect($tracker->state)->toBe(UploadTracker::STATE_COMPLETED)
        ->and(JobTrack::find($tracker->job_track_id)->state)->toBe(AbstractJob::STATE_COMPLETED);
});

it('refuses to queue anything when AI tagging is disabled', function () {
    Queue::fake();
    config(['dam.ai_tagging.enabled' => false]);

    $asset = existingAsset();

    $this->postJson(route('admin.dam.assets.mass_ai_tag'), ['indices' => [$asset->id]])
        ->assertStatus(422)
        ->assertJsonPath('message', trans('dam::app.admin.dam.tag.mass-action.ai-tag-unavailable'));

    Queue::assertNotPushed(TagAssetWithAi::class);
});

it('refuses to queue anything when no image capable platform is configured', function () {
    Queue::fake();
    MagicAIPlatform::query()->update(['status' => false]);

    $asset = existingAsset();

    $this->postJson(route('admin.dam.assets.mass_ai_tag'), ['indices' => [$asset->id]])
        ->assertStatus(422);

    Queue::assertNotPushed(TagAssetWithAi::class);
});

it('reports when none of the selected assets are images', function () {
    Queue::fake();

    $pdf = existingAsset('document', 'pdf');

    $this->postJson(route('admin.dam.assets.mass_ai_tag'), ['indices' => [$pdf->id]])
        ->assertStatus(422)
        ->assertJsonPath('message', trans('dam::app.admin.dam.tag.mass-action.ai-tag-none'));

    Queue::assertNotPushed(TagAssetWithAi::class);
});

it('validates the selected asset ids', function () {
    $this->postJson(route('admin.dam.assets.mass_ai_tag'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('indices');

    $this->postJson(route('admin.dam.assets.mass_ai_tag'), ['indices' => ['abc']])
        ->assertStatus(422)
        ->assertJsonValidationErrors('indices.0');
});

it('forbids users who cannot update assets and create tags', function () {
    Queue::fake();
    $this->loginWithPermissions('custom', ['dam', 'dam.asset', 'dam.asset.update']);

    $asset = existingAsset();

    $this->postJson(route('admin.dam.assets.mass_ai_tag'), ['indices' => [$asset->id]])
        ->assertForbidden();

    Queue::assertNotPushed(TagAssetWithAi::class);
});

it('checks directory access for the whole selection with a single lookup', function () {
    Queue::fake();

    $admin = $this->loginWithPermissions('custom', ['dam', 'dam.asset', 'dam.asset.update', 'dam.tags', 'dam.tags.create']);

    $granted = Directory::factory()->create(['name' => 'AiTagGranted']);
    $hidden = Directory::factory()->create(['name' => 'AiTagHidden']);

    DB::table('dam_directory_role')->insert([
        'directory_id' => $granted->id,
        'role_id'      => $admin->role_id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    DB::table('dam_role_settings')->updateOrInsert(
        ['role_id' => $admin->role_id],
        ['all_directories' => false, 'inherit_children' => false, 'created_at' => now(), 'updated_at' => now()]
    );

    app(DirectoryPermissionService::class)->flush();

    $visible = collect(range(1, 5))->map(function () use ($granted) {
        $asset = existingAsset();
        $asset->directories()->attach($granted->id);

        return $asset;
    });

    $blocked = existingAsset();
    $blocked->directories()->attach($hidden->id);

    $lookups = 0;

    DB::listen(function (QueryExecuted $query) use (&$lookups) {
        if (str_contains($query->sql, 'dam_asset_directory')) {
            $lookups++;
        }
    });

    $this->postJson(route('admin.dam.assets.mass_ai_tag'), [
        'indices' => [...$visible->pluck('id')->all(), $blocked->id],
    ])
        ->assertOk()
        ->assertJsonPath('queued', 5)
        ->assertJsonPath('skipped', 1);

    expect($lookups)->toBe(1);

    Queue::assertPushed(TagAssetWithAi::class, 5);
});

it('offers the AI tagging mass action on the asset grid only while AI tagging is available', function () {
    $actions = fn () => collect(app(AssetDataGrid::class)->toJson()->getData(true)['actions'] ?? [])
        ->merge(app(AssetDataGrid::class)->toJson()->getData(true)['mass_actions'] ?? [])
        ->pluck('url');

    expect($actions())->toContain(route('admin.dam.assets.mass_ai_tag'));

    config(['dam.ai_tagging.enabled' => false]);

    expect($actions())->not->toContain(route('admin.dam.assets.mass_ai_tag'));
});

it('queues AI tagging when an image is re-uploaded from the admin edit page', function () {
    Queue::fake();

    $directory = Directory::factory()->create(['name' => 'Root']);
    $asset = existingAsset();
    $asset->directories()->attach($directory->id);

    $this->postJson(route('admin.dam.assets.re_upload'), [
        'file'     => UploadedFile::fake()->image('replacement.png', 600, 600)->size(23),
        'asset_id' => $asset->id,
    ])->assertStatus(201);

    Queue::assertPushed(TagAssetWithAi::class, 1);
});

it('shows a generate AI tags button on the edit page of an image', function () {
    $asset = existingAsset();

    $this->get(route('admin.dam.assets.edit', $asset->id))
        ->assertOk()
        ->assertViewHas('canAiTag', true)
        ->assertSee(trans('dam::app.admin.dam.tag.mass-action.ai-tag'));
});

it('hides the generate AI tags button when AI tagging is disabled', function () {
    config(['dam.ai_tagging.enabled' => false]);

    $asset = existingAsset();

    $this->get(route('admin.dam.assets.edit', $asset->id))
        ->assertOk()
        ->assertViewHas('canAiTag', fn ($canAiTag) => $canAiTag === false);
});
