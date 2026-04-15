<?php

use Webkul\Category\Contracts\CategoryField;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\DAM\Listeners\Category as CategoryListener;
use Webkul\DAM\Listeners\Product as ProductListener;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Repositories\AssetResourceMappingRepository;

/** Product listener */
it('creates product asset mappings for active asset attributes with values', function () {
    $assetField = Mockery::mock();
    $assetField->code = 'image_field';
    $assetField->shouldReceive('getValueFromProductValues')->andReturn('1,2');

    $relation = Mockery::mock();
    $relation->shouldReceive('where')->with('attributes.type', 'asset')->andReturnSelf();
    $relation->shouldReceive('get')->andReturn(collect([$assetField]));

    $family = Mockery::mock();
    $family->shouldReceive('customAttributes')->andReturn($relation);

    $product = (object) [
        'values'           => ['common' => ['image_field' => '1,2']],
        'id'               => 42,
        'attribute_family' => $family,
    ];

    $assets = collect([new Asset(['id' => 1]), new Asset(['id' => 2])]);

    $assetRepo = Mockery::mock(AssetRepository::class);
    $assetRepo->shouldReceive('findWhereIn')->with('id', [1, 2])->andReturn($assets);

    $mappingRepo = Mockery::mock(AssetResourceMappingRepository::class);
    $mappingRepo->shouldReceive('createProductAssetMappings')
        ->once()
        ->with($assets, 42, 'image_field')
        ->andReturn([]);
    $mappingRepo->shouldNotReceive('deleteProductAssetMappings');

    (new ProductListener($assetRepo, $mappingRepo))->afterCreateOrupdate($product);
});

it('deletes product asset mappings when asset value is empty', function () {
    $assetField = Mockery::mock();
    $assetField->code = 'banner';
    $assetField->shouldReceive('getValueFromProductValues')->andReturn(null);

    $relation = Mockery::mock();
    $relation->shouldReceive('where')->andReturnSelf();
    $relation->shouldReceive('get')->andReturn(collect([$assetField]));

    $family = Mockery::mock();
    $family->shouldReceive('customAttributes')->andReturn($relation);

    $product = (object) [
        'values'           => [],
        'id'               => 7,
        'attribute_family' => $family,
    ];

    $assetRepo = Mockery::mock(AssetRepository::class);
    $assetRepo->shouldNotReceive('findWhereIn');

    $mappingRepo = Mockery::mock(AssetResourceMappingRepository::class);
    $mappingRepo->shouldReceive('deleteProductAssetMappings')
        ->once()
        ->with(7, 'banner');

    (new ProductListener($assetRepo, $mappingRepo))->afterCreateOrupdate($product);
});

/** Category listener */
it('creates category asset mappings using common additional data', function () {
    $assetField = Mockery::mock(CategoryField::class);
    $assetField->code = 'icon';
    $assetField->shouldReceive('isLocaleBasedField')->andReturn(false);

    $category = (object) [
        'additional_data' => ['common' => ['icon' => '5']],
        'id'              => 11,
    ];

    $assets = collect([new Asset(['id' => 5])]);

    $assetRepo = Mockery::mock(AssetRepository::class);
    $assetRepo->shouldReceive('findWhereIn')->with('id', ['5'])->andReturn($assets);

    $categoryFieldRepo = Mockery::mock(CategoryFieldRepository::class);
    $categoryFieldRepo->shouldReceive('findWhere')
        ->with(['status' => 1, 'type' => 'asset'])
        ->andReturn(collect([$assetField]));

    $mappingRepo = Mockery::mock(AssetResourceMappingRepository::class);
    $mappingRepo->shouldReceive('createCategoryAssetMappings')
        ->once()
        ->with($assets, 11, 'icon')
        ->andReturn([]);

    (new CategoryListener($assetRepo, $categoryFieldRepo, $mappingRepo))->afterUpdateOrCreate($category);
});

it('deletes category asset mappings when value is missing', function () {
    $assetField = Mockery::mock(CategoryField::class);
    $assetField->code = 'icon';
    $assetField->shouldReceive('isLocaleBasedField')->andReturn(false);

    $category = (object) [
        'additional_data' => ['common' => []],
        'id'              => 99,
    ];

    $assetRepo = Mockery::mock(AssetRepository::class);
    $assetRepo->shouldNotReceive('findWhereIn');

    $categoryFieldRepo = Mockery::mock(CategoryFieldRepository::class);
    $categoryFieldRepo->shouldReceive('findWhere')->andReturn(collect([$assetField]));

    $mappingRepo = Mockery::mock(AssetResourceMappingRepository::class);
    $mappingRepo->shouldReceive('deleteCategoryAssetMappings')
        ->once()
        ->with(99, 'icon');

    (new CategoryListener($assetRepo, $categoryFieldRepo, $mappingRepo))->afterUpdateOrCreate($category);
});
