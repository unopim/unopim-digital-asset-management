<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Directory;

beforeEach(function () {
    $this->loginAsAdmin();
    Storage::fake(Directory::getAssetDisk());
});

it('should create a file in private storage and return its path', function () {
    $file = UploadedFile::fake()->image('create.png', 100, 100);

    $response = $this->postJson(route('admin.dam.file.create'), ['file' => $file]);

    $response->assertOk()->assertJsonStructure(['path']);

    Storage::disk(Directory::getAssetDisk())->assertExists($response->json('path'));
});

it('should delete an existing file from private storage', function () {
    $disk = Directory::getAssetDisk();
    $path = 'random/files/sample.png';
    Storage::disk($disk)->put($path, 'dummy');

    $response = $this->deleteJson(route('admin.dam.file.delete'), ['path' => $path]);

    $response->assertOk()->assertJson(['status' => 'File deleted']);
    Storage::disk($disk)->assertMissing($path);
});

it('should return 404 when deleting a non-existent file', function () {
    $response = $this->deleteJson(route('admin.dam.file.delete'), ['path' => 'no/such/file.png']);

    $response->assertStatus(404)->assertJson(['error' => 'File not found']);
});

it('should update the file by replacing existing content', function () {
    $disk = Directory::getAssetDisk();
    $oldPath = 'random/files/old.png';
    Storage::disk($disk)->put($oldPath, 'old');

    $newFile = UploadedFile::fake()->image('new.png', 50, 50);

    $response = $this->call('PUT', route('admin.dam.file.update'), ['path' => $oldPath], [], ['file' => $newFile]);

    $response->assertOk()->assertJsonStructure(['new_path']);
    Storage::disk($disk)->assertMissing($oldPath);
    Storage::disk($disk)->assertExists($response->json('new_path'));
});

it('should return 404 when updating a non-existent file', function () {
    $newFile = UploadedFile::fake()->image('new.png', 50, 50);

    $response = $this->call('PUT', route('admin.dam.file.update'), ['path' => 'missing/path.png'], [], ['file' => $newFile]);

    $response->assertStatus(404)->assertJson(['error' => 'File not found']);
});

it('should fetch an existing file with the correct mime type', function () {
    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/sample.png';
    Storage::disk($disk)->put($path, 'binary-data');

    $response = $this->get(route('admin.dam.file.fetch', ['path' => $path]));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('image/');
});

it('should return 404 when fetching a non-existent file', function () {
    $response = $this->getJson(route('admin.dam.file.fetch', ['path' => 'assets/none.png']));

    $response->assertStatus(404)->assertJson(['error' => 'File not found']);
});

it('should serve a default thumbnail when the file is not an image', function () {
    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/document.pdf';
    Storage::disk($disk)->put($path, 'pdf-content');

    Storage::fake('public');
    Storage::disk('public')->put('dam/grid/file.svg', '<svg/>');

    $response = $this->call('GET', route('admin.dam.file.thumbnail'), ['path' => $path]);

    $response->assertOk();
});

it('should redirect thumbnail requests from unauthenticated users', function () {
    auth()->guard('admin')->logout();

    $response = $this->call('GET', route('admin.dam.file.thumbnail'), ['path' => 'assets/Root/x.png']);

    expect($response->getStatusCode())->toBe(302);
});

it('should redirect preview requests from unauthenticated users', function () {
    auth()->guard('admin')->logout();

    $response = $this->call('GET', route('admin.dam.file.preview'), ['path' => 'assets/Root/x.png']);

    expect($response->getStatusCode())->toBe(302);
});

it('should serve a default preview when the file does not exist', function () {
    Storage::fake('public');
    Storage::disk('public')->put('dam/preview/file.svg', '<svg/>');

    $response = $this->call('GET', route('admin.dam.file.preview'), ['path' => 'assets/Root/missing.pdf']);

    $response->assertOk();
});
