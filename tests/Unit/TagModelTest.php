<?php

use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Tag;

it('can create a tag', function () {
    $tag = Tag::create(['name' => 'test-tag']);

    expect($tag)->toBeInstanceOf(Tag::class);
    expect($tag->name)->toBe('test-tag');
});

it('has correct table name', function () {
    $tag = new Tag;
    expect($tag->getTable())->toBe('dam_tags');
});

it('has correct fillable attributes', function () {
    $tag = new Tag;
    expect($tag->getFillable())->toBe(['name']);
});

it('can have many assets', function () {
    $tag = Tag::create(['name' => 'multi-asset-tag']);
    $assets = Asset::factory()->count(3)->create();

    $tag->assets()->attach($assets->pluck('id'));

    expect($tag->assets)->toHaveCount(3);
});

it('can be detached from an asset', function () {
    $tag = Tag::create(['name' => 'detachable']);
    $asset = Asset::factory()->create();

    $tag->assets()->attach($asset->id);
    expect($tag->assets)->toHaveCount(1);

    $tag->assets()->detach($asset->id);
    expect($tag->refresh()->assets)->toHaveCount(0);
});

it('can have bidirectional relationship with assets', function () {
    $tag = Tag::create(['name' => 'bidirectional']);
    $asset = Asset::factory()->create();

    $asset->tags()->attach($tag->id);

    expect($asset->tags->first()->id)->toBe($tag->id);
    expect($tag->refresh()->assets->first()->id)->toBe($asset->id);
});
