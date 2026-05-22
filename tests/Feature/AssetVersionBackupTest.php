<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetVersion;
use Webkul\DAM\Models\Directory;

beforeEach(function () {
    $this->loginAsAdmin();
    $this->disk = Directory::getAssetDisk();
    Storage::fake($this->disk);
});

it('creates a version row when re-uploading an asset', function () {
    $directory = Directory::factory()->create(['name' => 'Root']);

    $existingName = 'before-'.uniqid().'.png';
    $existingPath = 'assets/Root/'.$existingName;
    Storage::disk($this->disk)->put($existingPath, 'BEFORE');

    $asset = Asset::factory()->create([
        'file_name' => $existingName,
        'extension' => 'png',
        'mime_type' => 'image/png',
        'file_size' => 6,
        'path'      => $existingPath,
    ]);
    $asset->directories()->sync([$directory->id]);

    $this->postJson(route('admin.dam.assets.re_upload'), [
        'asset_id' => $asset->id,
        'file'     => UploadedFile::fake()->image('after.png', 200, 200)->size(10),
    ])->assertStatus(201);

    $row = AssetVersion::where('asset_id', $asset->id)->first();
    expect($row)->not->toBeNull();
    expect($row->original_path)->toBe($existingPath);
    expect($row->original_file_name)->toBe($existingName);
    Storage::disk($this->disk)->assertExists($row->version_path);
});

it('creates a version row when uploading a file that overwrites an existing asset', function () {
    $directory = Directory::factory()->create(['name' => 'Root']);

    $clashName = 'clash-'.uniqid().'.png';
    $clashPath = 'assets/Root/'.$clashName;
    Storage::disk($this->disk)->put($clashPath, 'EXISTING');

    $asset = Asset::factory()->create([
        'file_name' => $clashName,
        'extension' => 'png',
        'mime_type' => 'image/png',
        'file_size' => 8,
        'path'      => $clashPath,
    ]);
    $asset->directories()->sync([$directory->id]);

    $response = $this->postJson(route('admin.dam.assets.upload'), [
        'files'        => [UploadedFile::fake()->image($clashName, 200, 200)->size(10)],
        'directory_id' => $directory->id,
    ]);

    $response->assertStatus(201);

    expect(AssetVersion::where('asset_id', $asset->id)->exists())->toBeTrue();
});

it('creates a version row when renaming an asset', function () {
    $directory = Directory::factory()->create(['name' => 'Root']);

    $oldName = 'rename-me-'.uniqid().'.pdf';
    $oldPath = 'assets/Root/'.$oldName;
    Storage::disk($this->disk)->put($oldPath, 'CONTENT');

    $asset = Asset::factory()->create([
        'file_name' => $oldName,
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 7,
        'path'      => $oldPath,
    ]);
    $asset->directories()->sync([$directory->id]);

    $this->postJson(route('admin.dam.assets.rename'), [
        'id'        => $asset->id,
        'file_name' => 'renamed-'.uniqid().'.pdf',
    ])->assertStatus(200);

    $row = AssetVersion::where('asset_id', $asset->id)->first();
    expect($row)->not->toBeNull();
    expect($row->original_file_name)->toBe($oldName);
    expect($row->original_path)->toBe($oldPath);
});

it('creates a version row when moving an asset to a different directory', function () {
    $rootDir = Directory::factory()->create(['name' => 'Root']);
    $targetDir = Directory::factory()->create(['name' => 'Archive', 'parent_id' => $rootDir->id]);

    Storage::disk($this->disk)->makeDirectory('assets/Root');
    Storage::disk($this->disk)->makeDirectory('assets/Root/Archive');

    $name = 'move-me-'.uniqid().'.jpg';
    $oldPath = 'assets/Root/'.$name;
    Storage::disk($this->disk)->put($oldPath, 'IMG');

    $asset = Asset::factory()->create([
        'file_name' => $name,
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 3,
        'path'      => $oldPath,
    ]);
    $asset->directories()->sync([$rootDir->id]);

    $this->post(route('admin.dam.assets.moved'), [
        'move_item_id'  => $asset->id,
        'new_parent_id' => $targetDir->id,
    ])->assertOk();

    $row = AssetVersion::where('asset_id', $asset->id)->first();
    expect($row)->not->toBeNull();
    expect($row->original_path)->toBe($oldPath);
});
