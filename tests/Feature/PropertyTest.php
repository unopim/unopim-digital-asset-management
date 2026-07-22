<?php

use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetProperty;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('should return all the property of the Asset', function () {
    $asset = Asset::factory()->create();

    $response = $this->get(route('admin.dam.asset.properties.index', $asset->id));
    $response->assertOk();
});

it('should create a new property', function () {
    $asset = Asset::factory()->create();

    $data = [
        'name'     => 'Text',
        'type'     => 'Text',
        'value'    => 'Testing',
        'language' => 'English',
    ];

    $response = $this->post(route('admin.dam.asset.property.store', $asset->id), $data);
    $response->assertOk();
    $response->assertSeeText(trans('dam::app.admin.dam.asset.properties.index.create-success'));
});

it('should reject a filterable property with no sort order', function () {
    $asset = Asset::factory()->create();

    $data = [
        'name'          => 'Text',
        'type'          => 'Text',
        'value'         => 'Testing',
        'language'      => 'English',
        'is_filterable' => 1,
        'sort_order'    => '',
    ];

    $this->postJson(route('admin.dam.asset.property.store', $asset->id), $data)
        ->assertStatus(422)
        ->assertJsonValidationErrors('sort_order')
        ->assertJsonFragment([
            'sort_order' => [trans('dam::app.admin.validation.property.sort-order.required')],
        ]);

    $this->assertDatabaseMissing('dam_asset_properties', ['dam_asset_id' => $asset->id]);
});

it('should accept zero as a valid sort order on a filterable property', function () {
    $asset = Asset::factory()->create();

    $data = [
        'name'          => 'Text',
        'type'          => 'Text',
        'value'         => 'Testing',
        'language'      => 'English',
        'is_filterable' => 1,
        'sort_order'    => 0,
    ];

    $this->postJson(route('admin.dam.asset.property.store', $asset->id), $data)
        ->assertOk();

    $this->assertDatabaseHas('dam_asset_properties', [
        'dam_asset_id'  => $asset->id,
        'is_filterable' => 1,
        'sort_order'    => 0,
    ]);
});

it('should reject a sort order above the allowed maximum', function () {
    $asset = Asset::factory()->create();

    $data = [
        'name'          => 'Text',
        'type'          => 'Text',
        'value'         => 'Testing',
        'language'      => 'English',
        'is_filterable' => 1,
        'sort_order'    => 10000,
    ];

    $this->postJson(route('admin.dam.asset.property.store', $asset->id), $data)
        ->assertStatus(422)
        ->assertJsonValidationErrors('sort_order');
});

it('should create a non filterable property without a sort order', function () {
    $asset = Asset::factory()->create();

    $data = [
        'name'     => 'Text',
        'type'     => 'Text',
        'value'    => 'Testing',
        'language' => 'English',
    ];

    $this->postJson(route('admin.dam.asset.property.store', $asset->id), $data)
        ->assertOk();

    $this->assertDatabaseHas('dam_asset_properties', [
        'dam_asset_id'  => $asset->id,
        'is_filterable' => 0,
        'sort_order'    => 0,
    ]);
});

it('should reject an update that makes a property filterable with no sort order', function () {
    $property = AssetProperty::factory()->create();

    $data = [
        'name'          => 'Text',
        'value'         => 'Testing',
        'is_filterable' => 1,
        'sort_order'    => '',
    ];

    $this->putJson(route('admin.dam.asset.properties.update', $property->id), $data)
        ->assertStatus(422)
        ->assertJsonValidationErrors('sort_order');
});

it('should return the edit page with data by Id', function () {
    $property = AssetProperty::factory()->create();

    $response = $this->get(route('admin.dam.asset.property.edit', $property->id));
    $response->assertOk();
});

it('should update the Property', function () {
    $property = AssetProperty::factory()->create();

    $data = [
        'name'  => 'Text',
        'value' => 'Testing',
    ];

    $this->put(route('admin.dam.asset.properties.update', $property->id), $data)
        ->assertOk()
        ->assertJson([
            'message' => trans('dam::app.admin.dam.asset.properties.index.update-success'),
        ]);
});

it('should delete the property by ID', function () {
    $asset = Asset::factory()->create();
    $assetId = $asset->id;

    $property = AssetProperty::factory()->create();

    $this->delete(route('admin.dam.asset.properties.delete', ['asset_id' => $assetId, 'id' => $property->id]))
        ->assertOk()
        ->assertJson([
            'message' => trans('dam::app.admin.dam.asset.properties.index.delete-success'),
        ]);
});

it('should delete all the properties at once', function () {
    $asset = Asset::factory()->create();
    $assetId = $asset->id;
    $properties = AssetProperty::factory()->count(3)->create([
        'dam_asset_id' => $assetId,
    ]);

    $propertyIds = $properties->pluck('id')->toArray();
    $response = $this->postJson(
        route('admin.dam.asset.properties.mass_delete', ['asset_id' => $assetId]),
        ['indices' => $propertyIds]
    );

    $response->assertOk()
        ->assertJsonFragment([
            'message' => trans('dam::app.admin.dam.asset.datagrid.mass-delete-success'),
        ]);

    foreach ($propertyIds as $id) {
        $this->assertDatabaseMissing('dam_asset_properties', ['id' => $id]);
    }

    $this->assertDatabaseMissing('dam_asset_properties', ['dam_asset_id' => $assetId]);
});
