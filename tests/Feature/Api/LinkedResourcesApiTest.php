<?php

use Webkul\Category\Models\Category;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetResourceMapping;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('returns a linked resource by id via api', function () {
    $asset = Asset::factory()->create();
    $category = Category::factory()->create();

    $mapping = AssetResourceMapping::create([
        'type'          => 'category',
        'category_id'   => $category->id,
        'dam_asset_id'  => $asset->id,
        'related_field' => 'icon',
    ]);

    $response = $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.linked_resource.get', $mapping->id));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $mapping->id);
});

it('returns 404 when linked resource is not found via api', function () {
    $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.linked_resource.get', 999999))
        ->assertStatus(404);
});
