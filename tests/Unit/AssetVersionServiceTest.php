<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Exceptions\AssetVersionMissingException;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetVersion;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\AssetVersionService;

beforeEach(function () {
    $this->loginAsAdmin();
    $this->disk = Directory::getAssetDisk();
    Storage::fake($this->disk);
    $this->service = app(AssetVersionService::class);
});

it('copies the live binary into the versions folder and upserts a row', function () {
    Storage::disk($this->disk)->put('assets/Root/old.jpg', 'OLD-BINARY');

    $asset = Asset::factory()->create([
        'file_name' => 'old.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 10,
        'path'      => 'assets/Root/old.jpg',
    ]);

    $this->service->backup($asset);

    Storage::disk($this->disk)->assertExists("versions/{$asset->id}/last.jpg");
    expect(Storage::disk($this->disk)->get("versions/{$asset->id}/last.jpg"))->toBe('OLD-BINARY');

    $row = AssetVersion::where('asset_id', $asset->id)->first();
    expect($row)->not->toBeNull();
    expect($row->original_path)->toBe('assets/Root/old.jpg');
    expect($row->original_file_name)->toBe('old.jpg');
    expect($row->original_extension)->toBe('jpg');
    expect($row->original_mime_type)->toBe('image/jpeg');
    expect($row->original_file_size)->toBe(10);
});

it('overwrites the existing backup when called a second time', function () {
    $asset = Asset::factory()->create([
        'file_name' => 'first.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 5,
        'path'      => 'assets/Root/first.jpg',
    ]);
    Storage::disk($this->disk)->put('assets/Root/first.jpg', 'FIRST');

    $this->service->backup($asset);

    $asset->update([
        'file_name' => 'second.jpg',
        'path'      => 'assets/Root/second.jpg',
    ]);
    Storage::disk($this->disk)->put('assets/Root/second.jpg', 'SECOND');

    $this->service->backup($asset);

    expect(AssetVersion::where('asset_id', $asset->id)->count())->toBe(1);
    expect(Storage::disk($this->disk)->get("versions/{$asset->id}/last.jpg"))->toBe('SECOND');
    expect(AssetVersion::where('asset_id', $asset->id)->first()->original_path)
        ->toBe('assets/Root/second.jpg');
});

it('is a no-op when the live binary is missing', function () {
    $asset = Asset::factory()->create([
        'file_name' => 'gone.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 0,
        'path'      => 'assets/Root/gone.jpg',
    ]);

    $this->service->backup($asset);

    expect(AssetVersion::where('asset_id', $asset->id)->exists())->toBeFalse();
    Storage::disk($this->disk)->assertMissing("versions/{$asset->id}/last.jpg");
});

it('restores the binary and metadata from the version row', function () {
    Storage::disk($this->disk)->put('assets/Root/old.jpg', 'OLD');
    $asset = Asset::factory()->create([
        'file_name' => 'old.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 3,
        'path'      => 'assets/Root/old.jpg',
    ]);

    $this->service->backup($asset);

    Storage::disk($this->disk)->delete('assets/Root/old.jpg');
    Storage::disk($this->disk)->put('assets/Root/new.jpg', 'NEW');
    $asset->update([
        'file_name' => 'new.jpg',
        'path'      => 'assets/Root/new.jpg',
        'file_size' => 3,
    ]);

    $this->service->restore($asset->id);

    $asset->refresh();
    expect($asset->path)->toBe('assets/Root/old.jpg');
    expect($asset->file_name)->toBe('old.jpg');
    expect(Storage::disk($this->disk)->get('assets/Root/old.jpg'))->toBe('OLD');

    $version = AssetVersion::where('asset_id', $asset->id)->first();
    expect($version->original_path)->toBe('assets/Root/new.jpg');
    expect(Storage::disk($this->disk)->get($version->version_path))->toBe('NEW');
});

it('throws when no version row exists', function () {
    $asset = Asset::factory()->create();

    $this->service->restore($asset->id);
})->throws(AssetVersionMissingException::class);

it('throws when the version binary is missing on disk', function () {
    Storage::disk($this->disk)->put('assets/Root/old.jpg', 'OLD');
    $asset = Asset::factory()->create([
        'file_name' => 'old.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 3,
        'path'      => 'assets/Root/old.jpg',
    ]);
    $this->service->backup($asset);

    Storage::disk($this->disk)->delete("versions/{$asset->id}/last.jpg");

    $this->service->restore($asset->id);
})->throws(AssetVersionMissingException::class);

it('deletes the cached history thumbnail when a new backup is taken', function () {
    Storage::disk($this->disk)->put('assets/Root/first.jpg', 'FIRST');
    $asset = Asset::factory()->create([
        'file_name' => 'first.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 5,
        'path'      => 'assets/Root/first.jpg',
    ]);
    $this->service->backup($asset);

    // Simulate a previously-served history thumbnail sitting on disk.
    Storage::disk($this->disk)->put(
        'versions/thumbnails/'.$asset->id.'/last.jpg',
        'STALE-THUMB'
    );
    Storage::disk($this->disk)->put(
        'versions/thumbnails/'.$asset->id.'/last.svg',
        'STALE-SVG'
    );

    // Swap to new content + re-backup.
    Storage::disk($this->disk)->put('assets/Root/second.jpg', 'SECOND');
    $asset->update(['file_name' => 'second.jpg', 'path' => 'assets/Root/second.jpg']);
    $this->service->backup($asset);

    Storage::disk($this->disk)->assertMissing('versions/thumbnails/'.$asset->id.'/last.jpg');
    Storage::disk($this->disk)->assertMissing('versions/thumbnails/'.$asset->id.'/last.svg');
});
