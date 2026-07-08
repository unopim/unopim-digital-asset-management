<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\DAM\Models\Asset;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();
});


function damBulkMakeFamilyWithAttribute(Attribute $attribute): AttributeFamily
{
    $group = AttributeGroup::factory()->create();
    $family = AttributeFamily::factory()->create();
    $family->familyGroups()->attach($group);

    $mapping = $family->attributeFamilyGroupMappings()->first();
    $mapping->customAttributes()->attach($attribute);

    return $family;
}

it('injects the asset bulk-edit cell and shared picker modal into the bulk edit page', function () {
    $assetAttribute = Attribute::factory()->create(['type' => 'asset']);
    $family = damBulkMakeFamilyWithAttribute($assetAttribute);
    $product = Product::factory()->create(['attribute_family_id' => $family->id]);

    // Populate the bulk-edit session with the asset attribute selected.
    $this->postJson(route('admin.catalog.products.bulkedit.filters'), [
        'indices' => [$product->id],
        'filter'  => [
            'filtered_attributes' => [
                ['id' => $assetAttribute->id],
            ],
        ],
    ])->assertOk();

    $content = $this->get(route('admin.catalog.products.bulkedit'))
        ->assertOk()
        ->getContent();

    // Admin dispatcher maps the `asset` type to the DAM-provided cell component.
    expect($content)->toContain("case 'asset': return 'v-spreadsheet-asset';");


    expect($content)->toContain('v-spreadsheet-asset-template');
    expect($content)->toContain('v-dam-asset-picker');
    expect($content)->toContain('dam-asset-picker:open');
});

it('persists selected asset ids and syncs asset mappings on bulk save', function () {
    $assetAttribute = Attribute::factory()->create(['type' => 'asset']);
    $family = damBulkMakeFamilyWithAttribute($assetAttribute);
    $product = Product::factory()->create([
        'attribute_family_id' => $family->id,
        'values'              => ['common' => []],
    ]);

    $assets = Asset::factory()->createMany(2);
    $assetIds = $assets->pluck('id')->all();


    $this->postJson(route('admin.catalog.products.bulk-edit.save'), [
        'data' => [
            $product->id => [
                $assetAttribute->code => $assetIds,
            ],
        ],
    ])->assertOk();

    $product->refresh();

    // The asset ids are stored on the product's common values.
    expect($product->values['common'][$assetAttribute->code] ?? null)->toEqual($assetIds);

    // The DAM listener created an asset<->product mapping for each selected asset.
    foreach ($assetIds as $assetId) {
        $this->assertDatabaseHas('dam_asset_resource_mappings', [
            'type'          => 'product',
            'product_id'    => $product->id,
            'dam_asset_id'  => $assetId,
            'related_field' => $assetAttribute->code,
        ]);
    }
});

it('clears asset values and removes mappings when the cell is emptied on bulk save', function () {
    $assetAttribute = Attribute::factory()->create(['type' => 'asset']);
    $family = damBulkMakeFamilyWithAttribute($assetAttribute);
    $product = Product::factory()->create([
        'attribute_family_id' => $family->id,
        'values'              => ['common' => []],
    ]);

    $asset = Asset::factory()->create();

    // First assign an asset.
    $this->postJson(route('admin.catalog.products.bulk-edit.save'), [
        'data' => [$product->id => [$assetAttribute->code => [$asset->id]]],
    ])->assertOk();

    $this->assertDatabaseHas('dam_asset_resource_mappings', [
        'product_id'    => $product->id,
        'dam_asset_id'  => $asset->id,
        'related_field' => $assetAttribute->code,
    ]);

    // Now clear it (the delete/clear icon sends an empty array).
    $this->postJson(route('admin.catalog.products.bulk-edit.save'), [
        'data' => [$product->id => [$assetAttribute->code => []]],
    ])->assertOk();

    $this->assertDatabaseMissing('dam_asset_resource_mappings', [
        'product_id'    => $product->id,
        'dam_asset_id'  => $asset->id,
        'related_field' => $assetAttribute->code,
    ]);
});
