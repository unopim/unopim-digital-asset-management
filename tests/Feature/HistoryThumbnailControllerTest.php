<?php

use Illuminate\Http\UploadedFile;
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

function seedImageAssetWithBackup(string $disk): Asset
{
    $img = UploadedFile::fake()->image('original.png', 600, 400)->size(20);
    Storage::disk($disk)->putFileAs('assets/Root', $img, 'orig.png');

    $asset = Asset::factory()->create([
        'file_name' => 'orig.png',
        'extension' => 'png',
        'mime_type' => 'image/png',
        'file_size' => $img->getSize(),
        'file_type' => 'image',
        'path'      => 'assets/Root/orig.png',
    ]);

    app(AssetVersionService::class)->backup($asset);

    Storage::disk($disk)->delete('assets/Root/orig.png');
    $asset->update(['path' => 'assets/Root/new.png']);

    return $asset;
}

it('returns 404 when no AssetVersion exists for the asset', function () {
    $asset = Asset::factory()->create();

    $this->get(route('admin.dam.history.thumbnail', ['asset' => $asset->id]))
        ->assertStatus(404);
});

it('returns 404 when the version binary is missing on disk', function () {
    $asset = Asset::factory()->create([
        'file_name' => 'gone.png',
        'extension' => 'png',
        'mime_type' => 'image/png',
        'file_size' => 1,
        'file_type' => 'image',
        'path'      => 'assets/Root/gone.png',
    ]);

    AssetVersion::create([
        'asset_id'           => $asset->id,
        'version_path'       => 'versions/'.$asset->id.'/last.png',
        'original_path'      => 'assets/Root/gone.png',
        'original_file_name' => 'gone.png',
        'original_extension' => 'png',
        'original_mime_type' => 'image/png',
        'original_file_type' => 'image',
        'original_file_size' => 1,
    ]);

    $this->get(route('admin.dam.history.thumbnail', ['asset' => $asset->id]))
        ->assertStatus(404);
});

it('returns a JPG thumbnail for an image AssetVersion', function () {
    $asset = seedImageAssetWithBackup($this->disk);

    $response = $this->get(route('admin.dam.history.thumbnail', ['asset' => $asset->id]));

    $response->assertStatus(200);
    expect($response->headers->get('Content-Type'))->toContain('image/jpeg');
    Storage::disk($this->disk)->assertExists('versions/thumbnails/'.$asset->id.'/last.jpg');
});

it('serves the cached file on the second request without regenerating', function () {
    $asset = seedImageAssetWithBackup($this->disk);

    $this->get(route('admin.dam.history.thumbnail', ['asset' => $asset->id]))->assertStatus(200);

    Storage::disk($this->disk)->put(
        'versions/thumbnails/'.$asset->id.'/last.jpg',
        'SENTINEL-NOT-A-REAL-JPEG'
    );

    $response = $this->get(route('admin.dam.history.thumbnail', ['asset' => $asset->id]));

    $response->assertStatus(200);
    expect($response->getContent())->toBe('SENTINEL-NOT-A-REAL-JPEG');
});

it('returns 404 for an unsupported original mime type', function () {
    $asset = Asset::factory()->create([
        'file_name' => 'archive.zip',
        'extension' => 'zip',
        'mime_type' => 'application/zip',
        'file_size' => 4,
        'file_type' => 'document',
        'path'      => 'assets/Root/archive.zip',
    ]);
    Storage::disk($this->disk)->put('versions/'.$asset->id.'/last.zip', 'PKAA');

    AssetVersion::create([
        'asset_id'           => $asset->id,
        'version_path'       => 'versions/'.$asset->id.'/last.zip',
        'original_path'      => 'assets/Root/archive.zip',
        'original_file_name' => 'archive.zip',
        'original_extension' => 'zip',
        'original_mime_type' => 'application/zip',
        'original_file_type' => 'document',
        'original_file_size' => 4,
    ]);

    $this->get(route('admin.dam.history.thumbnail', ['asset' => $asset->id]))
        ->assertStatus(404);
});

it('blocks unauthenticated requests', function () {
    auth()->guard('admin')->logout();

    $response = $this->get(route('admin.dam.history.thumbnail', ['asset' => 1]));

    // Admin middleware redirects to /login; if it ever changes to throw a 403
    // directly, that's also fine — both prove the endpoint isn't public.
    expect($response->getStatusCode())->toBeIn([302, 401, 403]);
});
