<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetVersion;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\AssetVersionService;

beforeEach(function () {
    $this->loginAsAdmin();
    $this->disk = Directory::getAssetDisk();
    Storage::fake($this->disk);
});

it('removes the version row and binary file when the asset is deleted', function () {
    Storage::disk($this->disk)->put('assets/Root/old.jpg', 'OLD');
    $asset = Asset::factory()->create([
        'file_name' => 'old.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 3,
        'path'      => 'assets/Root/old.jpg',
    ]);
    app(AssetVersionService::class)->backup($asset);

    $versionPath = AssetVersion::where('asset_id', $asset->id)->first()->version_path;
    Storage::disk($this->disk)->assertExists($versionPath);

    $asset->delete();

    expect(AssetVersion::where('asset_id', $asset->id)->exists())->toBeFalse();
    Storage::disk($this->disk)->assertMissing($versionPath);
});

it('removes the cached OLD-side history thumbnail when the asset is deleted', function () {
    Storage::disk($this->disk)->put('assets/Root/v.jpg', 'V');
    $asset = Asset::factory()->create([
        'file_name' => 'v.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 1,
        'path'      => 'assets/Root/v.jpg',
    ]);
    app(AssetVersionService::class)->backup($asset);

    Storage::disk($this->disk)->put('versions/thumbnails/'.$asset->id.'/last.jpg', 'CACHED');

    $asset->delete();

    Storage::disk($this->disk)->assertMissing('versions/thumbnails/'.$asset->id.'/last.jpg');
});
