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

    expect($content)->toContain("case 'asset': return 'v-spreadsheet-asset';");

    expect($content)->toContain('v-spreadsheet-asset-template');
    expect($content)->toContain('v-dam-asset-picker');
    expect($content)->toContain('dam-asset-picker:open');
});

it('syncs asset resource mappings when a product is saved with asset values', function () {
    $assetAttribute = Attribute::factory()->create(['type' => 'asset']);
    $family = damBulkMakeFamilyWithAttribute($assetAttribute);
    $product = Product::factory()->create([
        'attribute_family_id' => $family->id,
        'values'              => ['common' => []],
    ]);

    $assets = Asset::factory()->createMany(2);
    $assetIds = $assets->pluck('id')->all();

    $product->values = ['common' => [$assetAttribute->code => $assetIds]];
    $product->save();

    event('catalog.product.update.after', $product);

    $product->refresh();

    expect($product->values['common'][$assetAttribute->code] ?? null)->toEqual($assetIds);

    foreach ($assetIds as $assetId) {
        $this->assertDatabaseHas('dam_asset_resource_mappings', [
            'type'          => 'product',
            'product_id'    => $product->id,
            'dam_asset_id'  => $assetId,
            'related_field' => $assetAttribute->code,
        ]);
    }
});

it('removes asset resource mappings when the asset value is cleared', function () {
    $assetAttribute = Attribute::factory()->create(['type' => 'asset']);
    $family = damBulkMakeFamilyWithAttribute($assetAttribute);
    $product = Product::factory()->create([
        'attribute_family_id' => $family->id,
        'values'              => ['common' => []],
    ]);

    $asset = Asset::factory()->create();

    $product->values = ['common' => [$assetAttribute->code => [$asset->id]]];
    $product->save();
    event('catalog.product.update.after', $product);

    $this->assertDatabaseHas('dam_asset_resource_mappings', [
        'product_id'    => $product->id,
        'dam_asset_id'  => $asset->id,
        'related_field' => $assetAttribute->code,
    ]);

    $product->values = ['common' => [$assetAttribute->code => []]];
    $product->save();
    event('catalog.product.update.after', $product);

    $this->assertDatabaseMissing('dam_asset_resource_mappings', [
        'product_id'    => $product->id,
        'dam_asset_id'  => $asset->id,
        'related_field' => $assetAttribute->code,
    ]);
});
