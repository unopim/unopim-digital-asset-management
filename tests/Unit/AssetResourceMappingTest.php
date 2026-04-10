<?php

use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetResourceMapping;

it('can create an asset resource mapping', function () {
    $asset = Asset::factory()->create();

    $mapping = AssetResourceMapping::create([
        'type'          => 'product',
        'related_field' => 'image',
        'dam_asset_id'  => $asset->id,
    ]);

    expect($mapping)->toBeInstanceOf(AssetResourceMapping::class);
    expect($mapping->type)->toBe('product');
    expect($mapping->related_field)->toBe('image');
});

it('has correct table name', function () {
    $mapping = new AssetResourceMapping;
    expect($mapping->getTable())->toBe('dam_asset_resource_mappings');
});

it('has correct fillable attributes', function () {
    $mapping = new AssetResourceMapping;
    expect($mapping->getFillable())->toBe(['type', 'related_field', 'dam_asset_id', 'product_id', 'category_id']);
});

it('belongs to an asset', function () {
    $asset = Asset::factory()->create();
    $mapping = AssetResourceMapping::create([
        'type'          => 'product',
        'related_field' => 'thumbnail',
        'dam_asset_id'  => $asset->id,
    ]);

    expect($mapping->asset->id)->toBe($asset->id);
});

it('returns correct primary model id for history', function () {
    $asset = Asset::factory()->create();
    $mapping = AssetResourceMapping::create([
        'type'          => 'category',
        'related_field' => 'banner',
        'dam_asset_id'  => $asset->id,
    ]);

    expect($mapping->getPrimaryModelIdForHistory())->toBe($asset->id);
});

it('can create multiple mappings for same asset', function () {
    $asset = Asset::factory()->create();

    AssetResourceMapping::create([
        'type'          => 'product',
        'related_field' => 'image',
        'dam_asset_id'  => $asset->id,
    ]);

    AssetResourceMapping::create([
        'type'          => 'product',
        'related_field' => 'thumbnail',
        'dam_asset_id'  => $asset->id,
    ]);

    AssetResourceMapping::create([
        'type'          => 'category',
        'related_field' => 'banner',
        'dam_asset_id'  => $asset->id,
    ]);

    expect($asset->resources)->toHaveCount(3);
});

it('asset resources count blocks deletion', function () {
    $asset = Asset::factory()->create();

    AssetResourceMapping::create([
        'type'          => 'product',
        'related_field' => 'image',
        'dam_asset_id'  => $asset->id,
    ]);

    expect($asset->resources()->get()->count())->toBeGreaterThan(0);
});

it('asset with no resources allows deletion', function () {
    $asset = Asset::factory()->create();

    expect($asset->resources()->get()->count())->toBe(0);
});
