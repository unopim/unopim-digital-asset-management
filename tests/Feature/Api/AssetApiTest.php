<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;

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

it('returns a signed-url download response for an existing asset', function () {
    $disk = Directory::getAssetDisk();
    $path = 'assets/Root/download.png';
    Storage::disk($disk)->put($path, 'content');

    $asset = Asset::factory()->create(['path' => $path]);

    $response = $this->withHeaders($this->headers)
        ->get(route('admin.api.dam.assets.private.download', $asset->id));

    $response->assertOk();
});
