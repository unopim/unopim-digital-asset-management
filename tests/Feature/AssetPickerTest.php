<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;

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

it('should return local thumbnail url when disk is private', function () {
    Config::set('filesystems.default', Directory::ASSETS_DISK_PRIVATE);

    $asset = Asset::factory()->create(['path' => 'assets/Root/sample.jpg']);

    $response = $this->getJson(route('admin.dam.asset_picker.get_assets', ['assetIds' => (string) $asset->id]));

    $response->assertOk();
    $url = $response->json('0.url');

    expect($url)->toContain('/file/thumbnail');
});

it('should return thumbnail route url when disk is aws', function () {
    Config::set('filesystems.default', Directory::ASSETS_DISK_AWS);
    Storage::fake(Directory::ASSETS_DISK_AWS);

    $path = 'assets/Root/sample-'.uniqid().'.jpg';
    Storage::disk(Directory::ASSETS_DISK_AWS)->put($path, 'dummy');

    $asset = Asset::factory()->create(['path' => $path]);

    $response = $this->getJson(route('admin.dam.asset_picker.get_assets', ['assetIds' => (string) $asset->id]));

    $response->assertOk();
    $url = $response->json('0.url');

    expect($url)->toContain('/admin/dam/file/thumbnail');
    expect($url)->toBe(route('admin.dam.file.thumbnail', ['path' => urlencode($path)]));
});

it('should return thumbnail route url for audio asset so cover art is served', function () {
    Config::set('filesystems.default', Directory::ASSETS_DISK_PRIVATE);

    $path = 'assets/Root/track-'.uniqid().'.mp3';
    $asset = Asset::factory()->create([
        'path'      => $path,
        'file_type' => 'audio',
        'extension' => 'mp3',
        'mime_type' => 'audio/mpeg',
        'meta_data' => ['cover_art_path' => 'covers/'.uniqid().'.jpg'],
    ]);

    $response = $this->getJson(route('admin.dam.asset_picker.get_assets', ['assetIds' => (string) $asset->id]));

    $response->assertOk();
    $url = $response->json('0.url');

    expect($url)->toBe(route('admin.dam.file.thumbnail', ['path' => urlencode($path)]));
});
