<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\Tag;

/**
 * Create an asset and attach it to the given directory.
 */
function assetInDirectory(Directory $directory): Asset
{
    $asset = Asset::factory()->create();
    $asset->directories()->attach($directory->id);

    return $asset;
}

beforeEach(function () {
    $this->loginAsAdmin();
});

it('assigns tags to multiple assets at once', function () {
    $assets = Asset::factory()->count(3)->create();

    $response = $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices' => $assets->pluck('id')->all(),
        'tags'    => ['campaign', 'q3'],
    ]);

    $response->assertOk()->assertJson(['success' => true, 'count' => 3]);

    foreach ($assets as $asset) {
        expect($asset->refresh()->tags->pluck('name')->all())
            ->toContain('campaign')
            ->toContain('q3');
    }

    $this->assertDatabaseHas('dam_tags', ['name' => 'campaign']);
    $this->assertDatabaseHas('dam_tags', ['name' => 'q3']);
});

it('creates new tags on the fly when assigning', function () {
    $asset = Asset::factory()->create();

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices' => [$asset->id],
        'tags'    => ['brand-new'],
    ])->assertOk();

    $this->assertDatabaseHas('dam_tags', ['name' => 'brand-new']);
});

it('reuses an existing tag instead of creating a duplicate', function () {
    $tag = Tag::create(['name' => 'existing']);
    $asset = Asset::factory()->create();

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices' => [$asset->id],
        'tags'    => ['existing'],
    ])->assertOk();

    expect(Tag::where('name', 'existing')->count())->toBe(1);
    expect($asset->refresh()->tags->first()->id)->toBe($tag->id);
});

it('matches existing tags case-insensitively', function () {
    Tag::create(['name' => 'Summer']);
    $asset = Asset::factory()->create();

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices' => [$asset->id],
        'tags'    => ['summer'],
    ])->assertOk();

    expect(Tag::whereRaw('LOWER(name) = ?', ['summer'])->count())->toBe(1);
});

it('is additive and keeps tags the asset already has', function () {
    $asset = Asset::factory()->create();
    $existing = Tag::create(['name' => 'keep-me']);
    $asset->tags()->attach($existing->id);

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices' => [$asset->id],
        'tags'    => ['added'],
    ])->assertOk();

    $names = $asset->refresh()->tags->pluck('name')->all();

    expect($names)->toContain('keep-me')->toContain('added');
});

it('does not create duplicate pivot rows when a tag is already attached', function () {
    $asset = Asset::factory()->create();
    $tag = Tag::create(['name' => 'once']);
    $asset->tags()->attach($tag->id);

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices' => [$asset->id],
        'tags'    => ['once'],
    ])->assertOk();

    expect($asset->refresh()->tags()->where('tag_id', $tag->id)->count())->toBe(1);
});

it('dispatches the tag sync event per asset', function () {
    Event::fake(['core.model.proxy.sync.tag']);

    $assets = Asset::factory()->count(2)->create();

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices' => $assets->pluck('id')->all(),
        'tags'    => ['x'],
    ])->assertOk();

    Event::assertDispatchedTimes('core.model.proxy.sync.tag', 2);
});

it('requires at least one asset or folder', function () {
    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'tags' => ['x'],
    ])->assertStatus(422)->assertJson(['success' => false]);
});

it('validates that tags are required', function () {
    $asset = Asset::factory()->create();

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices' => [$asset->id],
    ])->assertStatus(422)->assertJsonValidationErrors(['tags']);
});

it('rejects tag names longer than 100 characters', function () {
    $asset = Asset::factory()->create();

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices' => [$asset->id],
        'tags'    => [str_repeat('a', 101)],
    ])->assertStatus(422)->assertJsonValidationErrors(['tags.0']);
});

it('tags every asset directly inside a selected folder', function () {
    $folder = Directory::create(['name' => 'campaign-assets']);
    $a = assetInDirectory($folder);
    $b = assetInDirectory($folder);
    $outsider = Asset::factory()->create();

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'directory_ids' => [$folder->id],
        'tags'          => ['promo'],
    ])->assertOk()->assertJson(['success' => true]);

    expect($a->refresh()->tags->pluck('name')->all())->toContain('promo');
    expect($b->refresh()->tags->pluck('name')->all())->toContain('promo');
    expect($outsider->refresh()->tags)->toHaveCount(0);
});

it('tags assets recursively through sub-folders', function () {
    $parent = Directory::create(['name' => 'root-folder']);
    $child = Directory::create(['name' => 'child', 'parent_id' => $parent->id]);
    $grand = Directory::create(['name' => 'grandchild', 'parent_id' => $child->id]);

    $atParent = assetInDirectory($parent);
    $atChild = assetInDirectory($child);
    $atGrand = assetInDirectory($grand);

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'directory_ids' => [$parent->id],
        'tags'          => ['archived'],
    ])->assertOk();

    foreach ([$atParent, $atChild, $atGrand] as $asset) {
        expect($asset->refresh()->tags->pluck('name')->all())->toContain('archived');
    }
});

it('combines explicitly selected assets and folders', function () {
    $folder = Directory::create(['name' => 'folder']);
    $inFolder = assetInDirectory($folder);
    $loose = Asset::factory()->create();

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'indices'       => [$loose->id],
        'directory_ids' => [$folder->id],
        'tags'          => ['mixed'],
    ])->assertOk();

    expect($inFolder->refresh()->tags->pluck('name')->all())->toContain('mixed');
    expect($loose->refresh()->tags->pluck('name')->all())->toContain('mixed');
});

it('does not create duplicate pivot rows when a folder is tagged twice', function () {
    $folder = Directory::create(['name' => 'folder']);
    $asset = assetInDirectory($folder);

    $payload = ['directory_ids' => [$folder->id], 'tags' => ['once']];

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), $payload)->assertOk();
    $this->postJson(route('admin.dam.assets.mass_assign_tags'), $payload)->assertOk();

    $tag = Tag::where('name', 'once')->first();
    expect(DB::table('dam_asset_tag')->where('asset_id', $asset->id)->where('tag_id', $tag->id)->count())->toBe(1);
});

it('creates a new tag on the fly when tagging a folder', function () {
    $folder = Directory::create(['name' => 'folder']);
    assetInDirectory($folder);

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'directory_ids' => [$folder->id],
        'tags'          => ['fresh-folder-tag'],
    ])->assertOk();

    $this->assertDatabaseHas('dam_tags', ['name' => 'fresh-folder-tag']);
});

it('tags a folder of many assets in a bounded number of queries (set-based, not per-asset)', function () {
    $folder = Directory::create(['name' => 'big-folder']);
    foreach (range(1, 40) as $i) {
        assetInDirectory($folder);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->postJson(route('admin.dam.assets.mass_assign_tags'), [
        'directory_ids' => [$folder->id],
        'tags'          => ['bulk'],
    ])->assertOk();

    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // The folder path must be set-based: a small, constant number of queries regardless of
    // the 40 assets. A per-asset implementation would run dozens of extra queries.
    expect($queryCount)->toBeLessThan(20);

    expect(DB::table('dam_asset_tag')
        ->join('dam_tags', 'dam_tags.id', '=', 'dam_asset_tag.tag_id')
        ->where('dam_tags.name', 'bulk')->count())->toBe(40);
});
