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

it('restores the previous binary on POST and returns 200', function () {
    Storage::disk($this->disk)->put('assets/Root/old.jpg', 'OLD');
    $asset = Asset::factory()->create([
        'file_name' => 'old.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 3,
        'path'      => 'assets/Root/old.jpg',
    ]);
    app(AssetVersionService::class)->backup($asset);

    Storage::disk($this->disk)->put('assets/Root/new.jpg', 'NEW');
    $asset->update(['file_name' => 'new.jpg', 'path' => 'assets/Root/new.jpg']);

    $this->postJson(route('admin.dam.history.restore', ['asset' => $asset->id]), [
        'path' => 'assets/Root/old.jpg',
    ])
        ->assertOk()
        ->assertJson(['status' => 'success']);

    $asset->refresh();
    expect($asset->path)->toBe('assets/Root/old.jpg');
    expect(Storage::disk($this->disk)->get('assets/Root/old.jpg'))->toBe('OLD');
});

it('returns 410 when no backup exists', function () {
    $asset = Asset::factory()->create();

    $this->postJson(route('admin.dam.history.restore', ['asset' => $asset->id]), [
        'path' => 'assets/Root/old.jpg',
    ])->assertStatus(410);
});

it('returns 409 when the path no longer matches the current backup', function () {
    Storage::disk($this->disk)->put('assets/Root/A.jpg', 'A');
    $asset = Asset::factory()->create([
        'file_name' => 'A.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 1,
        'path'      => 'assets/Root/A.jpg',
    ]);
    app(AssetVersionService::class)->backup($asset);

    Storage::disk($this->disk)->put('assets/Root/B.jpg', 'B');
    $asset->update(['path' => 'assets/Root/B.jpg', 'file_name' => 'B.jpg']);
    app(AssetVersionService::class)->backup($asset);

    $this->postJson(route('admin.dam.history.restore', ['asset' => $asset->id]), [
        'path' => 'assets/Root/A.jpg',
    ])->assertStatus(409);

    // Original path matches the current backup → 200
    $this->postJson(route('admin.dam.history.restore', ['asset' => $asset->id]), [
        'path' => AssetVersion::where('asset_id', $asset->id)->first()->original_path,
    ])->assertOk();
});
