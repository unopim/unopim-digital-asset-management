<?php

use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetProperty;

it('can create a property using factory', function () {
    $property = AssetProperty::factory()->create();

    expect($property)->toBeInstanceOf(AssetProperty::class);
    expect($property->name)->toBeString();
    expect($property->type)->toBe('Text');
    expect($property->dam_asset_id)->toBeInt();
});

it('has correct table name', function () {
    $property = new AssetProperty;
    expect($property->getTable())->toBe('dam_asset_properties');
});

it('has correct fillable attributes', function () {
    $property = new AssetProperty;
    expect($property->getFillable())->toBe(['name', 'type', 'language', 'value', 'dam_asset_id']);
});

it('belongs to an asset', function () {
    $asset = Asset::factory()->create();
    $property = AssetProperty::factory()->create(['dam_asset_id' => $asset->id]);

    expect($property->asset->id)->toBe($asset->id);
});

it('returns correct primary model id for history', function () {
    $asset = Asset::factory()->create();
    $property = AssetProperty::factory()->create(['dam_asset_id' => $asset->id]);

    expect($property->getPrimaryModelIdForHistory())->toBe($asset->id);
});

it('returns presenters array', function () {
    $presenters = AssetProperty::getPresenters();

    expect($presenters)->toBeArray();
    expect($presenters)->toHaveKeys(['name', 'type', 'value', 'language']);
});

it('has correct audit exclude columns', function () {
    $property = new AssetProperty;
    $auditExclude = (new ReflectionProperty($property, 'auditExclude'))->getValue($property);

    expect($auditExclude)->toContain('id', 'dam_asset_id');
});

it('can store different property types', function () {
    $asset = Asset::factory()->create();

    $textProp = AssetProperty::factory()->create([
        'dam_asset_id' => $asset->id,
        'name'         => 'Description',
        'type'         => 'Text',
        'value'        => 'A test description',
        'language'     => 'English',
    ]);

    $numProp = AssetProperty::factory()->create([
        'dam_asset_id' => $asset->id,
        'name'         => 'Width',
        'type'         => 'Number',
        'value'        => '1920',
        'language'     => 'English',
    ]);

    expect($textProp->type)->toBe('Text');
    expect($numProp->type)->toBe('Number');
    expect($asset->properties)->toHaveCount(2);
});
