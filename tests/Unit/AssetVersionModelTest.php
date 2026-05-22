<?php

use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetVersion;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('persists an asset version keyed by asset id', function () {
    $asset = Asset::factory()->create();

    $version = AssetVersion::create([
        'asset_id'           => $asset->id,
        'version_path'       => "versions/{$asset->id}/last.jpg",
        'original_path'      => 'assets/Root/old.jpg',
        'original_file_name' => 'old.jpg',
        'original_extension' => 'jpg',
        'original_mime_type' => 'image/jpeg',
        'original_file_size' => 1024,
    ]);

    expect($version->fresh()->asset_id)->toBe($asset->id);
});

it('belongs to an asset', function () {
    $asset = Asset::factory()->create();

    AssetVersion::create([
        'asset_id'           => $asset->id,
        'version_path'       => "versions/{$asset->id}/last.jpg",
        'original_path'      => 'assets/Root/old.jpg',
        'original_file_name' => 'old.jpg',
        'original_extension' => 'jpg',
        'original_mime_type' => 'image/jpeg',
        'original_file_size' => 1024,
    ]);

    $version = AssetVersion::where('asset_id', $asset->id)->first();
    expect($version->asset->id)->toBe($asset->id);
});
