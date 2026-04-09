<?php

use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetComments;
use Webkul\DAM\Models\AssetProperty;
use Webkul\DAM\Models\AssetResourceMapping;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\Tag;

it('can create an asset using factory', function () {
    $asset = Asset::factory()->create();

    expect($asset)->toBeInstanceOf(Asset::class);
    expect($asset->id)->toBeInt();
    expect($asset->file_name)->toBeString();
    expect($asset->file_type)->toBe('image');
    expect($asset->path)->toStartWith('assets/Root/');
});

it('has correct table name', function () {
    $asset = new Asset;
    expect($asset->getTable())->toBe('dam_assets');
});

it('has correct fillable attributes', function () {
    $asset = new Asset;
    expect($asset->getFillable())->toBe(['file_name', 'file_type', 'file_size', 'path', 'mime_type', 'extension']);
});

it('can have tags relationship', function () {
    $asset = Asset::factory()->create();
    $tag = Tag::create(['name' => 'test-tag']);

    $asset->tags()->attach($tag->id);

    expect($asset->tags)->toHaveCount(1);
    expect($asset->tags->first()->name)->toBe('test-tag');
});

it('can have multiple tags', function () {
    $asset = Asset::factory()->create();
    $tag1 = Tag::create(['name' => 'tag-1']);
    $tag2 = Tag::create(['name' => 'tag-2']);
    $tag3 = Tag::create(['name' => 'tag-3']);

    $asset->tags()->attach([$tag1->id, $tag2->id, $tag3->id]);

    expect($asset->refresh()->tags)->toHaveCount(3);
});

it('can have directories relationship', function () {
    $asset = Asset::factory()->create();
    $directory = Directory::factory()->create();

    $asset->directories()->attach($directory->id);

    expect($asset->directories)->toHaveCount(1);
    expect($asset->directories->first()->id)->toBe($directory->id);
});

it('can have properties relationship', function () {
    $asset = Asset::factory()->create();
    AssetProperty::factory()->count(2)->create(['dam_asset_id' => $asset->id]);

    expect($asset->properties)->toHaveCount(2);
});

it('can have comments relationship', function () {
    $asset = Asset::factory()->create();
    AssetComments::factory()->count(3)->create([
        'dam_asset_id' => $asset->id,
        'parent_id'    => null,
    ]);

    expect($asset->comments)->toHaveCount(3);
});

it('comments relationship only returns top-level comments', function () {
    $asset = Asset::factory()->create();
    $parent = AssetComments::factory()->create([
        'dam_asset_id' => $asset->id,
        'parent_id'    => null,
    ]);
    AssetComments::factory()->create([
        'dam_asset_id' => $asset->id,
        'parent_id'    => $parent->id,
    ]);

    expect($asset->comments)->toHaveCount(1);
    expect($asset->comments->first()->id)->toBe($parent->id);
});

it('can have resources relationship', function () {
    $asset = Asset::factory()->create();

    AssetResourceMapping::create([
        'type'          => 'product',
        'related_field' => 'image',
        'dam_asset_id'  => $asset->id,
    ]);

    expect($asset->resources)->toHaveCount(1);
});

it('can get path without file system root', function () {
    $asset = Asset::factory()->create(['path' => 'assets/Root/test.jpg']);

    expect($asset->getPathWithOutFileSystemRoot())->toBe('Root/test.jpg');
});

it('can get path without file system root for nested paths', function () {
    $asset = Asset::factory()->create(['path' => 'assets/Root/SubDir/test.jpg']);

    expect($asset->getPathWithOutFileSystemRoot())->toBe('Root/SubDir/test.jpg');
});
