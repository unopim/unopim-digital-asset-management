<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\MetadataExtractionService;

beforeEach(function () {
    Storage::fake(Directory::getAssetDisk());
    $this->headers = $this->getAuthenticationHeaders();
});

it('lists assets via the api index endpoint', function () {
    Asset::factory()->count(2)->create();

    $response = $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.assets.index'));

    $response->assertOk();
});

it('shows an asset via the api show endpoint', function () {
    $asset = Asset::factory()->create();

    $response = $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.assets.show', $asset->id));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.asset.id', $asset->id);
});

it('returns 404 when showing a non-existent asset via api', function () {
    $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.assets.show', 999999))
        ->assertStatus(404)
        ->assertJsonPath('success', false);
});

it('returns edit payload for an asset via api', function () {
    $asset = Asset::factory()->create();

    $response = $this->withHeaders($this->headers)
        ->putJson(route('admin.api.dam.assets.edit', $asset->id));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.asset.id', $asset->id);
});

it('returns 404 when editing a non-existent asset via api', function () {
    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.dam.assets.edit', 999999))
        ->assertStatus(404);
});

it('updates an asset via the api update endpoint', function () {
    $asset = Asset::factory()->create();

    $response = $this->withHeaders($this->headers)
        ->putJson(route('admin.api.dam.assets.update', $asset->id), [
            'file_name' => 'renamed.png',
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas($this->getFullTableName(Asset::class), [
        'id'        => $asset->id,
        'file_name' => 'renamed.png',
    ]);
});

it('returns 404 when updating a non-existent asset via api', function () {
    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.dam.assets.update', 999999), ['file_name' => 'x.png'])
        ->assertStatus(404);
});

it('uploads an asset via the api upload endpoint', function () {
    $disk = Directory::getAssetDisk();
    Storage::disk($disk)->makeDirectory('assets/New');

    $directory = Directory::factory()->create(['name' => 'New', 'parent_id' => null]);

    $file = UploadedFile::fake()->image('api-upload.png', 200, 200);

    $response = $this->withHeaders($this->headers)
        ->post(route('admin.api.dam.assets.upload'), [
            'files'        => [$file],
            'directory_id' => $directory->id,
        ]);

    $response->assertStatus(201)->assertJsonPath('success', true);
});

it('validates directory_id on api upload', function () {
    $response = $this->withHeaders($this->headers)
        ->postJson(route('admin.api.dam.assets.upload'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['directory_id']);
});

it('deletes an asset via the api destroy endpoint', function () {
    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/api-destroy.png';
    Storage::disk($disk)->put($path, 'x');

    $asset = Asset::factory()->create(['path' => $path]);

    $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.dam.assets.destroy', $asset->id))
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing($this->getFullTableName(Asset::class), ['id' => $asset->id]);
});

it('returns 404 when destroying a non-existent asset via api', function () {
    $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.dam.assets.destroy', 999999))
        ->assertStatus(404);
});

it('returns 404 when downloading a missing asset via api', function () {
    $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.assets.download', 999999))
        ->assertStatus(404);
});

it('returns a temporary signed download_url for non-s3 disks', function () {
    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/download-url.png';
    Storage::disk($disk)->put($path, 'binary-bytes');

    $asset = Asset::factory()->create([
        'file_name' => 'download-url.png',
        'path'      => $path,
    ]);

    $response = $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.assets.download', $asset->id));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['download_url']]);

    $downloadUrl = $response->json('data.download_url');

    expect($downloadUrl)
        ->toContain('signature=')
        ->toContain('expires=')
        ->toContain('signUrlDownload/'.$asset->id);
});

it('returns a 404 when the stored file is missing for download', function () {
    $asset = Asset::factory()->create(['path' => 'assets/Root/missing.png']);

    $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.assets.download', $asset->id))
        ->assertStatus(404)
        ->assertJsonPath('success', false);
});

it('streams the file via the signed download URL without bearer auth', function () {
    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/signed-stream.png';
    Storage::disk($disk)->put($path, 'binary-bytes');

    $asset = Asset::factory()->create([
        'file_name' => 'signed-stream.png',
        'path'      => $path,
    ]);

    $signedUrl = URL::temporarySignedRoute(
        'admin.api.dam.assets.private.download',
        now()->addMinutes(10),
        ['id' => $asset->id]
    );

    $relative = parse_url($signedUrl, PHP_URL_PATH).'?'.parse_url($signedUrl, PHP_URL_QUERY);

    $response = $this->get($relative);

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('signed-stream.png');
});

it('rejects the signed download URL when signature is invalid', function () {
    $asset = Asset::factory()->create(['path' => 'assets/Root/x.png']);

    Storage::disk(Directory::getAssetDisk())->put($asset->path, 'x');

    $this->get(route('admin.api.dam.assets.private.download', $asset->id).'?expires=9999999999&signature=deadbeef')
        ->assertForbidden();
});

it('rejects the signed-url download route without a valid signature', function () {
    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/download.png';
    Storage::disk($disk)->put($path, 'content');

    $asset = Asset::factory()->create(['path' => $path]);

    $this->get(route('admin.api.dam.assets.private.download', $asset->id))
        ->assertForbidden();
});

it('extracts and stores audio cover art when uploading audio via the api', function () {
    $disk = Directory::getAssetDisk();
    Storage::disk($disk)->makeDirectory('assets/AudioApi');

    $directory = Directory::factory()->create(['name' => 'AudioApi', 'parent_id' => null]);

    $mock = Mockery::mock(MetadataExtractionService::class);
    $mock->shouldReceive('extractMetadata')->andReturn(['Title' => 'Sample Track']);
    $mock->shouldReceive('extractCoverArtData')->andReturn('fake-binary-cover-bytes');
    $mock->shouldReceive('storeCoverArt')
        ->andReturnUsing(function ($data, $assetId, $diskName) {
            return 'covers/'.$assetId.'.jpg';
        });
    app()->instance(MetadataExtractionService::class, $mock);

    $file = UploadedFile::fake()->create('cover-track.mp3', 100, 'audio/mpeg');

    $response = $this->withHeaders($this->headers)
        ->post(route('admin.api.dam.assets.upload'), [
            'files'        => [$file],
            'directory_id' => $directory->id,
        ]);

    $response->assertStatus(201)->assertJsonPath('success', true);

    $assetId = $response->json('files.0.id');
    $asset = Asset::find($assetId);

    expect($asset->meta_data)->toHaveKey('cover_art_path')
        ->and($asset->meta_data['cover_art_path'])->toBe('covers/'.$asset->id.'.jpg');
});

it('does not attach cover art when uploading non-audio files via the api', function () {
    $disk = Directory::getAssetDisk();
    Storage::disk($disk)->makeDirectory('assets/ImageApi');

    $directory = Directory::factory()->create(['name' => 'ImageApi', 'parent_id' => null]);

    $mock = Mockery::mock(MetadataExtractionService::class);
    $mock->shouldReceive('extractMetadata')->andReturn(['Width' => 200]);
    $mock->shouldNotReceive('extractCoverArtData');
    $mock->shouldNotReceive('storeCoverArt');
    app()->instance(MetadataExtractionService::class, $mock);

    $file = UploadedFile::fake()->image('image.png', 200, 200);

    $this->withHeaders($this->headers)
        ->post(route('admin.api.dam.assets.upload'), [
            'files'        => [$file],
            'directory_id' => $directory->id,
        ])->assertStatus(201);
});

it('extracts and stores audio cover art when reuploading audio via the api', function () {
    $disk = Directory::getAssetDisk();
    Storage::disk($disk)->makeDirectory('assets/AudioReup');

    $directory = Directory::factory()->create(['name' => 'AudioReup', 'parent_id' => null]);

    $initialPath = 'assets/AudioReup/old.mp3';
    Storage::disk($disk)->put($initialPath, 'old audio bytes');
    $asset = Asset::factory()->create([
        'file_name' => 'old.mp3',
        'mime_type' => 'audio/mpeg',
        'extension' => 'mp3',
        'path'      => $initialPath,
    ]);
    $asset->directories()->attach($directory->id);

    $mock = Mockery::mock(MetadataExtractionService::class);
    $mock->shouldReceive('extractMetadata')->andReturn(['Title' => 'Updated Track']);
    $mock->shouldReceive('extractCoverArtData')->andReturn('fake-binary-cover-bytes');
    $mock->shouldReceive('storeCoverArt')
        ->andReturnUsing(function ($data, $assetId, $diskName) {
            return 'covers/'.$assetId.'.jpg';
        });
    app()->instance(MetadataExtractionService::class, $mock);

    $newFile = UploadedFile::fake()->create('new-track.mp3', 100, 'audio/mpeg');

    $response = $this->withHeaders($this->headers)
        ->post(route('admin.api.dam.assets.reUpload'), [
            'file'     => $newFile,
            'asset_id' => $asset->id,
        ]);

    $response->assertStatus(201)->assertJsonPath('success', true);

    $asset->refresh();

    expect($asset->meta_data)->toHaveKey('cover_art_path')
        ->and($asset->meta_data['cover_art_path'])->toBe('covers/'.$asset->id.'.jpg');
});

it('skips cover art on reupload when the new file is not audio', function () {
    $disk = Directory::getAssetDisk();
    Storage::disk($disk)->makeDirectory('assets/MixedReup');

    $directory = Directory::factory()->create(['name' => 'MixedReup', 'parent_id' => null]);

    $initialPath = 'assets/MixedReup/old.png';
    Storage::disk($disk)->put($initialPath, 'old image bytes');
    $asset = Asset::factory()->create([
        'file_name' => 'old.png',
        'mime_type' => 'image/png',
        'extension' => 'png',
        'path'      => $initialPath,
    ]);
    $asset->directories()->attach($directory->id);

    $mock = Mockery::mock(MetadataExtractionService::class);
    $mock->shouldReceive('extractMetadata')->andReturn(['Width' => 100]);
    $mock->shouldNotReceive('extractCoverArtData');
    $mock->shouldNotReceive('storeCoverArt');
    app()->instance(MetadataExtractionService::class, $mock);

    $newFile = UploadedFile::fake()->image('new.png', 100, 100);

    $this->withHeaders($this->headers)
        ->post(route('admin.api.dam.assets.reUpload'), [
            'file'     => $newFile,
            'asset_id' => $asset->id,
        ])->assertStatus(201);
});
