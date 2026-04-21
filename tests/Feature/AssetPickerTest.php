<?php

use Webkul\DAM\Models\Asset;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('should return the asset picker index page', function () {
    $response = $this->get(route('admin.dam.asset_picker.index'));
    $response->assertOk();
});

it('should fetch assets by ids', function () {
    $assets = Asset::factory()->createMany(3);
    $ids = $assets->pluck('id')->implode(',');

    $response = $this->getJson(route('admin.dam.asset_picker.get_assets', ['assetIds' => $ids]));

    $response->assertOk();
    $response->assertJsonCount(3);
    $response->assertJsonStructure([
        '*' => ['id', 'url', 'value', 'file_name', 'file_type', 'storage_file_path'],
    ]);
});

it('should return empty array when no asset ids provided', function () {
    $response = $this->getJson(route('admin.dam.asset_picker.get_assets'));

    $response->assertOk()
        ->assertJson([]);
});

it('should fetch a single asset by id', function () {
    $asset = Asset::factory()->create();

    $response = $this->getJson(route('admin.dam.asset_picker.get_assets', ['assetIds' => (string) $asset->id]));

    $response->assertOk();
    $response->assertJsonCount(1);
    $response->assertJsonFragment(['id' => $asset->id]);
});
