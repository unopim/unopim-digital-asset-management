<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Jobs\DeleteDirectory as DeleteDirectoryJob;
use Webkul\DAM\Jobs\RenameDirectory as RenameDirectoryJob;
use Webkul\DAM\Models\Directory;

beforeEach(function () {
    Storage::fake(Directory::getAssetDisk());
    $this->headers = $this->getAuthenticationHeaders();
});

it('returns the directory tree via api index', function () {
    Directory::factory()->create(['name' => 'Root']);

    $response = $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.directory.index'));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data']);
});

it('creates a new directory via api', function () {
    $disk = Directory::getAssetDisk();
    Storage::disk($disk)->makeDirectory('assets/ApiRoot');

    $parent = Directory::factory()->create(['name' => 'ApiRoot', 'parent_id' => null]);

    $response = $this->withHeaders($this->headers)
        ->postJson(route('admin.api.dam.directory.store'), [
            'name'      => 'API Child',
            'parent_id' => $parent->id,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('dam_directories', ['name' => 'API Child', 'parent_id' => $parent->id]);
});

it('returns a single directory by id via api', function () {
    $directory = Directory::factory()->create();

    $response = $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.directory.get', $directory->id));

    $response->assertOk()->assertJsonPath('success', true);
});

it('returns 404 when directory not found via api', function () {
    $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.directory.get', 999999))
        ->assertStatus(404);
});

it('renames a directory and dispatches rename job via api', function () {
    $disk = Directory::getAssetDisk();
    Storage::disk($disk)->makeDirectory('assets/OldApiDir');
    Bus::fake();

    $directory = Directory::factory()->create(['name' => 'OldApiDir', 'parent_id' => null]);

    $response = $this->withHeaders($this->headers)
        ->putJson(route('admin.api.dam.directory.update', $directory->id), [
            'name' => 'RenamedApiDir',
        ]);

    $response->assertOk()->assertJsonPath('success', true);
    Bus::assertDispatched(RenameDirectoryJob::class);
});

it('returns 404 when updating a non-existent directory via api', function () {
    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.dam.directory.update', 999999), ['name' => 'X'])
        ->assertStatus(404);
});

it('dispatches delete job for a deletable directory via api', function () {
    Bus::fake();
    $directory = Directory::factory()->create();

    $response = $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.dam.directory.delete', $directory->id));

    $response->assertStatus(202)->assertJsonPath('success', true);
    Bus::assertDispatched(DeleteDirectoryJob::class);
});

it('returns 404 when deleting a non-existent directory via api', function () {
    $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.dam.directory.delete', 999999))
        ->assertStatus(404);
});
