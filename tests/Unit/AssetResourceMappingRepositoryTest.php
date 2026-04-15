<?php

use Webkul\Category\Models\Category;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetResourceMapping;
use Webkul\DAM\Repositories\AssetResourceMappingRepository;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->repo = app(AssetResourceMappingRepository::class);
});

function productId(): int
{
    return Product::factory()->simple()->create()->id;
}

function categoryId(): int
{
    return Category::factory()->create()->id;
}

it('creates product asset mappings for a list of assets', function () {
    $assets = Asset::factory()->createMany(2);
    $productId = productId();

    $created = $this->repo->createProductAssetMappings($assets, $productId, 'image');

    expect($created)->toHaveCount(2);

    foreach ($assets as $asset) {
        $this->assertDatabaseHas('dam_asset_resource_mappings', [
            'product_id'    => $productId,
            'dam_asset_id'  => $asset->id,
            'related_field' => 'image',
            'type'          => AssetResourceMappingRepository::PRODUCT_TYPE_MAPPING,
        ]);
    }
});

it('does not duplicate product mappings when assets already linked', function () {
    $asset = Asset::factory()->create();
    $productId = productId();

    $this->repo->createProductAssetMappings(collect([$asset]), $productId, 'image');
    $this->repo->createProductAssetMappings(collect([$asset]), $productId, 'image');

    $count = AssetResourceMapping::where('product_id', $productId)
        ->where('related_field', 'image')
        ->count();

    expect($count)->toBe(1);
});

it('removes stale product mappings on subsequent sync', function () {
    [$first, $second] = Asset::factory()->createMany(2);
    $productId = productId();

    $this->repo->createProductAssetMappings(collect([$first, $second]), $productId, 'image');
    $this->repo->createProductAssetMappings(collect([$second]), $productId, 'image');

    $this->assertDatabaseMissing('dam_asset_resource_mappings', [
        'product_id'   => $productId,
        'dam_asset_id' => $first->id,
    ]);
    $this->assertDatabaseHas('dam_asset_resource_mappings', [
        'product_id'   => $productId,
        'dam_asset_id' => $second->id,
    ]);
});

it('creates category asset mappings with category type', function () {
    $assets = Asset::factory()->createMany(2);
    $categoryId = categoryId();

    $created = $this->repo->createCategoryAssetMappings($assets, $categoryId, 'icon');

    expect($created)->toHaveCount(2);
    foreach ($assets as $asset) {
        $this->assertDatabaseHas('dam_asset_resource_mappings', [
            'category_id'   => $categoryId,
            'dam_asset_id'  => $asset->id,
            'related_field' => 'icon',
            'type'          => AssetResourceMappingRepository::CATEGORY_TYPE_MAPPING,
        ]);
    }
});

it('deletes product mappings for a given product and field', function () {
    $assets = Asset::factory()->createMany(2);
    $productId = productId();
    $this->repo->createProductAssetMappings($assets, $productId, 'banner');

    $this->repo->deleteProductAssetMappings($productId, 'banner');

    $this->assertDatabaseMissing('dam_asset_resource_mappings', [
        'product_id'    => $productId,
        'related_field' => 'banner',
    ]);
});

it('deletes category mappings for a given category and field', function () {
    $assets = Asset::factory()->createMany(2);
    $categoryId = categoryId();
    $this->repo->createCategoryAssetMappings($assets, $categoryId, 'thumbnail');

    $this->repo->deleteCategoryAssetMappings($categoryId, 'thumbnail');

    $this->assertDatabaseMissing('dam_asset_resource_mappings', [
        'category_id'   => $categoryId,
        'related_field' => 'thumbnail',
    ]);
});

it('accepts asset ids as integers when creating mappings', function () {
    $asset = Asset::factory()->create();
    $productId = productId();

    $created = $this->repo->createProductAssetMappings([$asset->id], $productId, 'image');

    expect($created)->toHaveCount(1);
    $this->assertDatabaseHas('dam_asset_resource_mappings', [
        'product_id'   => $productId,
        'dam_asset_id' => $asset->id,
    ]);
});
